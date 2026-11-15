<?php

namespace Tests\Feature;

use App\Mail\BookingFlowMail;
use App\Models\Booking;
use App\Models\Car;
use App\Models\Service;
use App\Models\User;
use App\Services\BookingNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BookingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    private User $employee;

    private User $admin;

    private Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        $this->client = User::factory()->create(['role' => 'Cliente', 'points' => 0, 'phone_number' => '3311112222']);
        $this->employee = User::factory()->create(['role' => 'Empleado', 'is_active' => true, 'phone_number' => '3333334444']);
        $this->admin = User::factory()->create(['role' => 'Admin', 'is_active' => true]);

        $service = Service::create([
            'name' => 'Lavado ecológico en seco',
            'description' => 'Servicio de prueba',
            'price' => 200,
            'duration_minutes' => 60,
            'type' => 'Seco',
            'points' => 30,
            'is_active' => true,
        ]);

        $this->booking = Booking::create([
            'user_id' => $this->client->id,
            'employee_id' => null,
            'address' => 'Av. Prueba 123, Colonia Centro',
            'street' => 'Av. Prueba',
            'street_number' => '123',
            'neighborhood' => 'Centro',
            'scheduled_at' => now()->addDay()->setTime(10, 0),
            'status' => 'pending',
            'assignment_requested_at' => now(),
            'payment_method' => 'Tarjeta',
            'total_price' => 200,
        ]);

        $this->booking->services()->attach($service->id, ['price_at_booking' => 200]);
    }

    public function test_booking_creation_notifies_admins_and_client(): void
    {
        BookingNotifier::bookingCreated($this->booking->load('services', 'client'), $this->client->id);

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $this->admin->id,
            'title' => 'Reserva por asignar',
        ]);

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $this->client->id,
            'title' => 'Reserva recibida',
        ]);

        Mail::assertSent(BookingFlowMail::class, fn (BookingFlowMail $mail) => $mail->hasTo($this->admin->email));
        Mail::assertSent(BookingFlowMail::class, fn (BookingFlowMail $mail) => $mail->hasTo($this->client->email));
    }

    public function test_admin_assignment_confirms_the_booking_and_notifies_both_parties(): void
    {
        $this->actingAs($this->admin)
            ->patch(route('admin.bookings.assignEmployee', $this->booking), ['employee_id' => $this->employee->id])
            ->assertSessionHasNoErrors();

        $this->booking->refresh();

        $this->assertSame($this->employee->id, $this->booking->employee_id);
        $this->assertSame('confirmed', $this->booking->status);

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $this->client->id,
            'title' => 'Colaborador confirmado',
        ]);

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $this->employee->id,
            'title' => 'Nuevo servicio asignado',
        ]);

        Mail::assertSent(BookingFlowMail::class, fn (BookingFlowMail $mail) => $mail->hasTo($this->client->email));
    }

    public function test_employee_needs_an_eta_to_start_the_route_and_window_is_seven_minutes(): void
    {
        $this->assignEmployee();

        $this->actingAs($this->employee)
            ->patch(route('employee.bookings.updateStatus', $this->booking), ['status' => 'on_way'])
            ->assertSessionHasErrors('eta_minutes');

        $this->actingAs($this->employee)
            ->patch(route('employee.bookings.updateStatus', $this->booking), ['status' => 'on_way', 'eta_minutes' => 30])
            ->assertSessionHasNoErrors();

        $this->booking->refresh();

        $this->assertSame('on_way', $this->booking->status);
        $this->assertSame(30, $this->booking->arrival_eta_minutes);
        $this->assertNotNull($this->booking->en_route_at);
        $this->assertNotNull($this->booking->arrivalWindowLabel());
        $this->assertSame(7, (int) $this->booking->arrival_window_start->diffInMinutes($this->booking->arrival_window_end));

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $this->client->id,
            'title' => 'Reserva con el colaborador en camino',
        ]);
    }

    public function test_arrival_registers_the_time_and_inspection_requires_client_confirmation(): void
    {
        $this->assignEmployee();

        $this->actingAs($this->employee)
            ->patch(route('employee.bookings.updateStatus', $this->booking), ['status' => 'on_way', 'eta_minutes' => 15]);

        $this->actingAs($this->employee)
            ->patch(route('employee.bookings.updateStatus', $this->booking), ['status' => 'in_progress'])
            ->assertSessionHasNoErrors();

        $this->booking->refresh();
        $this->assertSame('in_progress', $this->booking->status);
        $this->assertNotNull($this->booking->arrived_at);

        // El colaborador registra las marcas del vehículo.
        $this->actingAs($this->employee)
            ->post(route('employee.bookings.inspection.store', $this->booking), [
                'markers' => [
                    ['view' => 'left', 'x' => 40, 'y' => 55, 'type' => 'scratch', 'note' => 'Rayón previo'],
                    ['view' => 'front', 'x' => 70, 'y' => 40, 'type' => 'chip'],
                ],
                'notes' => 'Detalles estéticos previos al lavado.',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('booking_inspections', ['booking_id' => $this->booking->id]);

        // No se puede finalizar sin la confirmación del cliente.
        $this->actingAs($this->employee)
            ->patch(route('employee.bookings.updateStatus', $this->booking), ['status' => 'completed'])
            ->assertSessionHas('error');

        $this->actingAs($this->client)
            ->patch(route('client.bookings.inspection.confirm', $this->booking))
            ->assertSessionHasNoErrors();

        $this->assertNotNull($this->booking->inspection()->first()->client_confirmed_at);

        // Tampoco puede finalizar antes de 15 minutos desde su llegada.
        $this->actingAs($this->employee)
            ->patch(route('employee.bookings.updateStatus', $this->booking), ['status' => 'completed'])
            ->assertSessionHas('error');

        $this->travel(16)->minutes();

        $this->actingAs($this->employee)
            ->patch(route('employee.bookings.updateStatus', $this->booking), ['status' => 'completed'])
            ->assertSessionHasNoErrors();

        $this->booking->refresh();
        $this->assertSame('completed', $this->booking->status);
        $this->assertNotNull($this->booking->completed_at);
    }

    public function test_client_confirmation_awards_points_and_tip_is_registered(): void
    {
        $this->completeService();

        $this->actingAs($this->client)
            ->patch(route('client.bookings.confirmCompletion', $this->booking), ['air_freshener_accepted' => true])
            ->assertSessionHasNoErrors();

        $this->booking->refresh();
        $this->client->refresh();

        $this->assertNotNull($this->booking->client_confirmed_at);
        $this->assertTrue($this->booking->air_freshener_accepted);
        $this->assertSame(30, $this->booking->points_awarded);
        $this->assertSame(30, $this->client->points);

        // Calificar suma puntos extra (10 + 5 por comentario).
        $this->actingAs($this->client)
            ->post(route('client.bookings.review', $this->booking), ['rating' => 5, 'comment' => 'Excelente servicio.'])
            ->assertSessionHasNoErrors();

        $this->client->refresh();
        $this->assertSame(45, $this->client->points);

        // Propina digital.
        $this->actingAs($this->client)
            ->post(route('client.bookings.tip', $this->booking), ['amount' => 50, 'method' => 'digital'])
            ->assertSessionHasNoErrors();

        $this->booking->refresh();
        $this->assertSame('50.00', $this->booking->tip_amount);
        $this->assertSame('digital', $this->booking->tip_method);

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $this->employee->id,
            'title' => '¡Recibiste una propina!',
        ]);
    }

    public function test_employee_cannot_complete_before_fifteen_minutes_since_arrival(): void
    {
        $this->assignEmployee();

        $this->actingAs($this->employee)
            ->patch(route('employee.bookings.updateStatus', $this->booking), ['status' => 'on_way', 'eta_minutes' => 10]);

        $this->actingAs($this->employee)
            ->patch(route('employee.bookings.updateStatus', $this->booking), ['status' => 'in_progress']);

        $this->booking->refresh();
        $this->assertFalse($this->booking->canBeCompleted());
        $this->assertSame(15, (int) $this->booking->arrived_at->diffInMinutes($this->booking->completionAvailableAt()));

        $this->travel(10)->minutes();
        $this->assertFalse($this->booking->fresh()->canBeCompleted());

        $this->travel(6)->minutes();
        $this->assertTrue($this->booking->fresh()->canBeCompleted());
    }

    public function test_employee_must_write_a_reason_to_cancel_the_service(): void
    {
        $this->assignEmployee();

        $this->actingAs($this->employee)
            ->patch(route('employee.bookings.updateStatus', $this->booking), ['status' => 'on_way', 'eta_minutes' => 12]);

        $this->actingAs($this->employee)
            ->patch(route('employee.bookings.updateStatus', $this->booking), ['status' => 'in_progress']);

        $this->actingAs($this->employee)
            ->patch(route('employee.bookings.updateStatus', $this->booking), ['status' => 'cancelled'])
            ->assertSessionHasErrors('cancel_reason');

        $this->assertSame('in_progress', $this->booking->fresh()->status);

        $this->actingAs($this->employee)
            ->patch(route('employee.bookings.updateStatus', $this->booking), [
                'status' => 'cancelled',
                'cancel_reason' => 'El cliente no se encontraba en el domicilio.',
            ])
            ->assertSessionHasNoErrors();

        $this->booking->refresh();
        $this->assertSame('cancelled', $this->booking->status);
        $this->assertSame('El cliente no se encontraba en el domicilio.', $this->booking->cancel_reason);

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $this->client->id,
            'title' => 'Reserva cancelada',
        ]);
    }

    public function test_client_and_employee_can_chat_with_up_to_three_images(): void
    {
        Storage::fake('public');

        $this->assignEmployee();

        $this->actingAs($this->client)
            ->post(route('client.bookings.chat.store', $this->booking), ['body' => 'Hola, el portón es negro.'])
            ->assertOk()
            ->assertJsonPath('messages.0.body', 'Hola, el portón es negro.');

        $this->actingAs($this->employee)
            ->post(route('employee.bookings.chat.store', $this->booking), [
                'body' => 'Perfecto, ya voy en camino.',
                'images' => [
                    UploadedFile::fake()->image('frente.jpg'),
                    UploadedFile::fake()->image('lateral.jpg'),
                    UploadedFile::fake()->image('trasera.jpg'),
                ],
            ])
            ->assertOk()
            ->assertJsonCount(2, 'messages');

        $this->assertDatabaseCount('booking_messages', 2);
        $this->assertDatabaseCount('booking_message_images', 3);

        // No se permiten más de 3 imágenes por mensaje.
        $this->actingAs($this->employee)
            ->post(route('employee.bookings.chat.store', $this->booking), [
                'images' => [
                    UploadedFile::fake()->image('a.jpg'),
                    UploadedFile::fake()->image('b.jpg'),
                    UploadedFile::fake()->image('c.jpg'),
                    UploadedFile::fake()->image('d.jpg'),
                ],
            ])
            ->assertSessionHasErrors('images');

        $this->assertDatabaseCount('booking_messages', 2);

        $messages = $this->actingAs($this->client)
            ->get(route('client.bookings.chat.index', $this->booking))
            ->assertOk()
            ->json('messages');

        $this->assertCount(2, $messages);
        $this->assertTrue($messages[0]['mine']);
        $this->assertCount(3, $messages[1]['images']);
    }

    public function test_outsiders_cannot_read_or_write_the_booking_chat(): void
    {
        $this->assignEmployee();

        $outsider = User::factory()->create(['role' => 'Cliente']);

        $this->actingAs($outsider)
            ->get(route('client.bookings.chat.index', $this->booking))
            ->assertForbidden();

        $this->actingAs($outsider)
            ->post(route('client.bookings.chat.store', $this->booking), ['body' => 'Hola'])
            ->assertForbidden();
    }

    public function test_client_rejects_the_courtesy_air_freshener(): void
    {
        $this->completeService();

        $this->actingAs($this->client)
            ->patch(route('client.bookings.confirmCompletion', $this->booking), ['air_freshener_accepted' => false])
            ->assertSessionHasNoErrors();

        $this->booking->refresh();

        $this->assertFalse($this->booking->air_freshener_accepted);
        $this->assertNull($this->booking->air_freshener);
        $this->assertNotNull($this->booking->client_confirmed_at);
    }

    public function test_booking_payload_includes_the_car_and_its_photos(): void
    {
        $car = Car::create([
            'user_id' => $this->client->id,
            'brand' => 'Nissan',
            'model' => 'Versa',
            'year' => 2021,
            'color' => 'Blanco',
            'plates' => 'ABC-123-A',
        ]);

        $car->addMedia(UploadedFile::fake()->image('frente.jpg'))
            ->withCustomProperties(['side' => 'front'])
            ->toMediaCollection('photos');

        $this->booking->update(['car_id' => $car->id]);
        $this->assignEmployee();

        $this->actingAs($this->employee)
            ->get(route('employee.bookings.show', $this->booking))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('booking.car.brand', 'Nissan')
                ->where('booking.car.plates', 'ABC-123-A')
                ->where('booking.car.has_photos', true)
                ->has('booking.car.photos', 4));
    }

    private function assignEmployee(): void
    {
        $this->actingAs($this->admin)
            ->patch(route('admin.bookings.assignEmployee', $this->booking), ['employee_id' => $this->employee->id]);

        $this->booking->refresh();
    }

    private function completeService(): void
    {
        $this->assignEmployee();

        $this->actingAs($this->employee)->patch(route('employee.bookings.updateStatus', $this->booking), [
            'status' => 'on_way',
            'eta_minutes' => 20,
        ]);

        $this->actingAs($this->employee)->patch(route('employee.bookings.updateStatus', $this->booking), [
            'status' => 'in_progress',
        ]);

        $this->actingAs($this->employee)->post(route('employee.bookings.inspection.store', $this->booking), [
            'markers' => [['view' => 'rear', 'x' => 50, 'y' => 50, 'type' => 'dent']],
        ]);

        $this->actingAs($this->client)->patch(route('client.bookings.inspection.confirm', $this->booking));

        $this->travel(16)->minutes();

        $this->actingAs($this->employee)->patch(route('employee.bookings.updateStatus', $this->booking), [
            'status' => 'completed',
        ]);

        $this->booking->refresh();
    }
}
