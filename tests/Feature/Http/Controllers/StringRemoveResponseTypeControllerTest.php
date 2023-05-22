<?php

use App\Models\Outbound;
use App\Models\ResponseType;
use App\Models\User;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseCount;

it('should create and redirect', function () {
    $user = User::factory()->create();

    $outbound = Outbound::factory()->create();

    assertDatabaseCount('response_types', 0);

    actingAs($user)
        ->get(route('response_types.string_remove.create', ['outbound' => $outbound->id]));

    assertDatabaseCount('response_types', 1);
});

it('should do update to response type string replace', function () {
    $user = User::factory()->create();

    $outbound = Outbound::factory()->chatUi()->create();

    $responseType = ResponseType::factory()
        ->create([
            'outbound_id' => $outbound->id,
        ]);

    $this->actingAs($user)
        ->put(route('response_types.string_remove.update', [
            'outbound' => $outbound->id,
            'response_type' => $responseType->id,
        ]), [
            'meta_data' => [
                'strings' => ['foo', 'bar'],
            ],
        ])->assertRedirectToRoute(
            'outbounds.chat_ui.show', [
                'outbound' => $outbound->id,
                'project' => $outbound->project->id,
            ]
        );

    $this->assertEquals(['foo', 'bar'], $responseType->refresh()->meta_data['strings']);
});