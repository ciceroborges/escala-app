<?php

namespace Tests\Feature;

use App\Enums\MemberRole;
use App\Models\ActivityLog;
use App\Models\Assignment;
use App\Models\Member;
use App\Models\Position;
use App\Models\Schedule;
use App\Models\User;
use App\Services\Contracts\IAuthService;
use App\Services\Contracts\IMinistryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleFlowTest extends TestCase
{
    use RefreshDatabase;

    private function ministryWithTeam(): array
    {
        $leader = User::factory()->create();
        $ministry = app(IMinistryService::class)->create($leader, ['name' => 'Louvor']);
        $positions = Position::where('ministry_id', $ministry->id)->pluck('id', 'name');

        foreach ([['Ana', ['Vocal']], ['Bia', ['Vocal']], ['Caio', ['Vocal']], ['Duda', ['Vocal']], ['Edu', ['Vocal']],
            ['Fabi', ['Teclado']], ['Gabi', ['Teclado']], ['Hugo', ['Bateria']], ['Iago', ['Bateria']],
            ['Juca', ['Mesa']], ['Kika', ['Mesa']]] as [$name, $roles]) {
            $member = Member::create(['ministry_id' => $ministry->id, 'name' => $name, 'email' => strtolower($name).'@x.com']);
            $member->positions()->sync(array_map(fn ($r) => $positions[$r], $roles));
        }

        return [$leader, $ministry];
    }

    public function test_webmaster_exists_as_user_one(): void
    {
        $this->assertSame('Webmaster', User::find(User::WEBMASTER_ID)?->name);
    }

    public function test_guest_is_sent_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_pre_registered_member_is_linked_on_first_login(): void
    {
        [, $ministry] = $this->ministryWithTeam();

        $user = app(IAuthService::class)->loginWithGoogle('g-123', 'ANA@x.com', 'Ana', null);

        $this->assertSame($user->id, Member::where('ministry_id', $ministry->id)->where('email', 'ana@x.com')->value('user_id'));
        $this->actingAs($user)->get('/')->assertOk();
    }

    public function test_dev_login_only_exists_locally(): void
    {
        $this->post(route('dev.login'), ['email' => 'qualquer@x.com'])->assertNotFound();
    }

    public function test_user_without_ministry_is_asked_to_create_one(): void
    {
        $this->actingAs(User::factory()->create())->get('/')->assertRedirect(route('ministries.index'));
    }

    public function test_leader_opens_month_generates_and_publishes(): void
    {
        [$leader, $ministry] = $this->ministryWithTeam();
        $this->actingAs($leader)->withSession(['ministry_id' => $ministry->id]);

        $this->post(route('schedules.store'), ['month' => '2026-11'])->assertRedirect();
        $schedule = Schedule::where('ministry_id', $ministry->id)->firstOrFail();
        $this->assertCount(5, $schedule->worshipServices);

        $this->post(route('schedules.generate', $schedule))->assertRedirect();
        $this->assertTrue(Assignment::whereIn('worship_service_id', $schedule->worshipServices->modelKeys())->exists());

        $this->post(route('schedules.publish', $schedule))->assertRedirect(route('schedules.messages', $schedule));
        $this->get(route('schedules.messages', $schedule))->assertOk();
    }

    public function test_every_action_is_logged_with_its_author(): void
    {
        [$leader, $ministry] = $this->ministryWithTeam();
        $this->actingAs($leader)->withSession(['ministry_id' => $ministry->id]);

        $this->post(route('schedules.store'), ['month' => '2026-11']);
        $schedule = Schedule::firstOrFail();
        $this->post(route('schedules.generate', $schedule));

        $this->assertSame(0, ActivityLog::whereNull('user_id')->count());
        $this->assertTrue(ActivityLog::where('action', 'schedules.generated')->where('user_id', $leader->id)->exists());
        // o setup do teste rodou sem login: fica no nome do webmaster
        $this->assertTrue(ActivityLog::where('action', 'members.created')->where('user_id', User::WEBMASTER_ID)->exists());
    }

    public function test_regular_member_cannot_manage_schedule_or_see_draft_team(): void
    {
        [$leader, $ministry] = $this->ministryWithTeam();
        $user = User::factory()->create(['email' => 'ana@x.com']);
        Member::where('email', 'ana@x.com')->update(['user_id' => $user->id, 'role' => MemberRole::Member]);

        $this->actingAs($leader)->withSession(['ministry_id' => $ministry->id])->post(route('schedules.store'), ['month' => '2026-11']);
        $schedule = Schedule::firstOrFail();
        $this->post(route('schedules.generate', $schedule));

        $this->actingAs($user)->withSession(['ministry_id' => $ministry->id]);
        $this->post(route('schedules.generate', $schedule))->assertForbidden();
        $this->get(route('members.index'))->assertForbidden();

        $page = $this->get(route('schedules.show', $schedule))->assertOk()->viewData('page');
        $this->assertSame(0, collect($page['props']['services'])->sum(fn ($s) => count($s['assignments'])));
    }

    public function test_member_cannot_reach_another_ministry(): void
    {
        [$leader] = $this->ministryWithTeam();
        $other = app(IMinistryService::class)->create(User::factory()->create(), ['name' => 'Outro']);

        $this->actingAs($leader)->post(route('ministries.switch', $other->id))->assertForbidden();
    }
}
