<?php

namespace App\Http\Controllers;

use App\Models\Reward;
use App\Models\RewardRedemption;
use App\Models\User;
use App\Models\UserReward;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class UserRewardController extends Controller
{
    /**
     * Catálogo de premios y canjes del cliente.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $rewards = Reward::query()
            ->active()
            ->orderBy('points_cost')
            ->get()
            ->map(fn (Reward $reward) => [
                'id' => $reward->id,
                'name' => $reward->name,
                'description' => $reward->description,
                'points_cost' => $reward->points_cost,
                'image_url' => $reward->image_url,
                'can_redeem' => $user->points >= $reward->points_cost,
                'missing_points' => max(0, $reward->points_cost - $user->points),
            ])
            ->values();

        $promotionHistory = UserReward::query()
            ->with('promotion')
            ->where('user_id', $user->id)
            ->where('is_claimed', true)
            ->get()
            ->map(function (UserReward $reward) {
                $promotion = $reward->promotion;

                $detail = null;

                if ($promotion) {
                    $value = (float) $promotion->discount_value;
                    $detail = $promotion->discount_type === 'Porcentaje'
                        ? rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.') . '%'
                        : '$' . number_format($value, 2) . ' MXN';
                }

                return [
                    'id' => 'promo-' . $reward->id,
                    'kind' => 'promotion',
                    'title' => $promotion?->code ?? 'Recompensa',
                    'description' => $promotion?->description,
                    'detail' => $detail,
                    'date' => optional($reward->claimed_at)->translatedFormat('d M Y'),
                    'date_iso' => optional($reward->claimed_at)->toIso8601String(),
                ];
            });

        $redemptionHistory = RewardRedemption::query()
            ->with('reward')
            ->where('user_id', $user->id)
            ->get()
            ->map(fn (RewardRedemption $redemption) => [
                'id' => 'reward-' . $redemption->id,
                'kind' => 'reward',
                'title' => $redemption->reward?->name ?? 'Premio canjeado',
                'description' => $redemption->reward?->description,
                'detail' => $redemption->points_spent . ' pts',
                'date' => optional($redemption->redeemed_at)->translatedFormat('d M Y'),
                'date_iso' => optional($redemption->redeemed_at)->toIso8601String(),
            ]);

        $history = $promotionHistory
            ->concat($redemptionHistory)
            ->sortByDesc('date_iso')
            ->values();

        return Inertia::render('Client/Rewards/Index', [
            'points' => $user->points,
            'rewards' => $rewards,
            'history' => $history,
        ]);
    }

    /**
     * Canjea un premio del catálogo descontando los puntos del cliente.
     */
    public function redeem(Request $request, Reward $reward)
    {
        abort_unless($reward->is_active, 404);

        DB::transaction(function () use ($request, $reward) {
            $user = User::query()->lockForUpdate()->findOrFail($request->user()->id);

            if ($user->points < $reward->points_cost) {
                throw ValidationException::withMessages([
                    'redeem' => 'No tienes puntos suficientes para canjear este premio.',
                ]);
            }

            $user->decrement('points', $reward->points_cost);

            RewardRedemption::create([
                'user_id' => $user->id,
                'reward_id' => $reward->id,
                'points_spent' => $reward->points_cost,
                'redeemed_at' => now(),
            ]);
        });

        return redirect()
            ->back()
            ->with('success', '¡Canjeaste "' . $reward->name . '"! Lo verás en tus premios canjeados.');
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        //
    }

    public function show(UserReward $userReward)
    {
        //
    }

    public function edit(UserReward $userReward)
    {
        //
    }

    public function update(Request $request, UserReward $userReward)
    {
        //
    }

    public function destroy(UserReward $userReward)
    {
        //
    }
}
