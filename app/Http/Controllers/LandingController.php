<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class LandingController extends Controller
{
    /**
     * Display the landing page.
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        return Inertia::render('Landing/Index');
    }

    /**
     * Display the services page.
     *
     * @return \Inertia\Response
     */
    public function services()
    {
        return Inertia::render('Landing/Servicios');
    }

    /**
     * Display the appointment booking page.
     *
     * @return \Inertia\Response
     */
    public function appointment(Request $request)
    {
        // Pasamos el parámetro 'service' de la URL como un prop llamado 'serviceId' a la vista
        return Inertia::render('Landing/Agendar', [
            'serviceId' => $request->query('service'),
        ]);
    }

     /**
     * Display the promotions page.
     *
     * @return \Inertia\Response
     */
    public function promotions()
    {
        return Inertia::render('Landing/Promociones');
    }
}
