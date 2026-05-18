<?php

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('guest is redirected to login', function () {
    $this->get(route('apps.index'))
        ->assertRedirect(route('login'));
});

test('authenticated user can visit apps index', function () {
    $this->actingAs($this->user)
        ->get(route('apps.index'))
        ->assertOk();
});

test('apps list shows existing apps', function () {
    $app = createApp(['name' => 'My Reverb App']);

    $this->actingAs($this->user)
        ->get(route('apps.index'))
        ->assertOk()
        ->assertSee('My Reverb App');
});

test('can search apps by name', function () {
    createApp(['name' => 'Alpha App']);
    createApp(['name' => 'Beta App']);

    $this->actingAs($this->user);

    Livewire::actingAs($this->user)
        ->test('pages::apps.index')
        ->set('search', 'Alpha')
        ->assertSee('Alpha App')
        ->assertDontSee('Beta App');
});

test('can create a new app via Livewire', function () {
    Cache::shouldReceive('forget')->with('reverb_apps')->once();

    Livewire::actingAs($this->user)
        ->test('pages::apps.index')
        ->call('openCreate')
        ->set('name', 'New Test App')
        ->set('host', 'example.com')
        ->set('port', 443)
        ->set('scheme', 'https')
        ->set('allowed_origins', '*')
        ->set('ping_interval', 60)
        ->set('activity_timeout', 30)
        ->set('max_message_size', 10000)
        ->set('accept_client_events_from', 'members')
        ->set('rate_limiting_enabled', false)
        ->set('rate_limit_max_attempts', 60)
        ->set('rate_limit_decay_seconds', 60)
        ->set('rate_limit_terminate_on_limit', false)
        ->set('is_active', true)
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('apps', ['name' => 'New Test App']);
});

test('can update an existing app via Livewire', function () {
    $app = createApp(['name' => 'Original Name']);

    Cache::shouldReceive('forget')->with('reverb_apps')->once();

    Livewire::actingAs($this->user)
        ->test('pages::apps.index')
        ->call('openEdit', $app->id)
        ->set('name', 'Updated Name')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('apps', ['id' => $app->id, 'name' => 'Updated Name']);
});

test('can delete an app via Livewire', function () {
    $app = createApp(['name' => 'App To Delete']);

    Cache::shouldReceive('forget')->with('reverb_apps')->once();

    Livewire::actingAs($this->user)
        ->test('pages::apps.index')
        ->call('openDelete', $app->id)
        ->call('delete')
        ->assertHasNoErrors();

    $this->assertSoftDeleted('apps', ['id' => $app->id]);
});

test('reverb cache is cleared on create', function () {
    Cache::shouldReceive('forget')->with('reverb_apps')->once();

    Livewire::actingAs($this->user)
        ->test('pages::apps.index')
        ->call('openCreate')
        ->set('name', 'Cache Test App')
        ->set('host', 'localhost')
        ->set('port', 443)
        ->set('scheme', 'https')
        ->set('allowed_origins', '*')
        ->set('ping_interval', 60)
        ->set('activity_timeout', 30)
        ->set('max_message_size', 10000)
        ->set('accept_client_events_from', 'members')
        ->set('rate_limiting_enabled', false)
        ->set('rate_limit_max_attempts', 60)
        ->set('rate_limit_decay_seconds', 60)
        ->set('rate_limit_terminate_on_limit', false)
        ->set('is_active', true)
        ->call('save');
});

test('reverb cache is cleared on update', function () {
    $app = createApp();

    Cache::shouldReceive('forget')->with('reverb_apps')->once();

    Livewire::actingAs($this->user)
        ->test('pages::apps.index')
        ->call('openEdit', $app->id)
        ->set('name', 'Cache Update Test')
        ->call('save');
});

test('reverb cache is cleared on delete', function () {
    $app = createApp();

    Cache::shouldReceive('forget')->with('reverb_apps')->once();

    Livewire::actingAs($this->user)
        ->test('pages::apps.index')
        ->call('openDelete', $app->id)
        ->call('delete');
});
