<?php

declare(strict_types=1);

namespace Tests\Feature\Mail;

use App\Enums\OrganizationUserRole;
use App\Mail\OrganizationInvitationMail;
use App\Mail\TrialExpiringReminder;
use App\Mail\WelcomeOrganizer;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MjmlMailablesTest extends TestCase
{
    public function test_welcome_organizer_renders_the_mjml_template(): void
    {
        $organization = Organization::factory()->make([
            'name' => 'Kalfa Events',
        ]);
        $user = User::factory()->make([
            'name' => 'Netanel',
        ]);
        $pdfUrl = 'https://example.com/guide.pdf';

        $mailable = new WelcomeOrganizer($organization, $user, $pdfUrl);

        $mailable->assertHasSubject('Welcome to Kalfa - Kalfa Events');
        $mailable->assertSeeInHtml('Netanel');
        $mailable->assertSeeInHtml('Kalfa Events');
        $mailable->assertSeeInHtml($pdfUrl);
        $mailable->assertSeeInHtml(route('dashboard'));

        $html = $mailable->render();

        $this->assertStringContainsString('lang="he"', $html);
        $this->assertStringContainsString('dir="rtl"', $html);
        $this->assertStringContainsString('text-align:right', $html);
        $this->assertStringContainsString('<bdi dir="auto">Netanel</bdi>', $html);
        $this->assertStringContainsString('<bdi dir="auto">Kalfa Events</bdi>', $html);
        $this->assertStringContainsString('<bdi dir="ltr">Kalfa</bdi>', $html);
    }

    public function test_organization_invitation_renders_the_mjml_template(): void
    {
        $organization = Organization::factory()->make([
            'name' => 'Kalfa Events',
        ]);
        $invitation = new OrganizationInvitation([
            'email' => 'invitee@example.com',
            'role' => OrganizationUserRole::Admin,
            'token' => 'invite-token-123',
            'expires_at' => Carbon::parse('2026-04-01 12:30:00'),
        ]);
        $invitation->setRelation('organization', $organization);

        $mailable = new OrganizationInvitationMail($invitation);

        $mailable->assertHasSubject('You have been invited to join Kalfa Events');
        $mailable->assertSeeInHtml('Kalfa Events');
        $mailable->assertSeeInHtml('admin');
        $mailable->assertSeeInHtml(route('invitations.accept', ['token' => 'invite-token-123']));

        $html = $mailable->render();

        $this->assertStringContainsString('lang="he"', $html);
        $this->assertStringContainsString('dir="rtl"', $html);
        $this->assertStringContainsString('text-align:right', $html);
        $this->assertStringContainsString('<bdi dir="auto">Kalfa Events</bdi>', $html);
        $this->assertStringContainsString('<bdi dir="auto">admin</bdi>', $html);
        $this->assertStringContainsString('<bdi dir="ltr">2026-04-01 12:30</bdi>', $html);
    }

    public function test_trial_expiring_reminder_renders_the_hebrew_mjml_template(): void
    {
        $mailable = new TrialExpiringReminder(
            ownerName: 'Netanel',
            daysRemaining: 3,
            organizationName: 'Kalfa Test Organization',
            planName: 'Pro Trial',
            trialEndsAt: Carbon::parse('2026-03-25 10:00:00'),
            selectPlanUrl: route('billing.account'),
            mailLocale: 'he',
        );

        $mailable->assertHasSubject('תזכורת: תקופת הניסיון שלך עומדת להסתיים');
        $mailable->assertSeeInHtml('Netanel');
        $mailable->assertSeeInHtml('Kalfa Test Organization');
        $mailable->assertSeeInHtml(route('billing.account'));

        $html = $mailable->render();

        $this->assertStringContainsString('lang="he"', $html);
        $this->assertStringContainsString('dir="rtl"', $html);
        $this->assertStringContainsString('text-align:right', $html);
        $this->assertStringContainsString('<bdi dir="auto">Netanel</bdi>', $html);
        $this->assertStringContainsString('<bdi dir="auto">Kalfa Test Organization</bdi>', $html);
        $this->assertStringContainsString('<bdi dir="auto">Pro Trial</bdi>', $html);
        $this->assertStringContainsString('<bdi dir="ltr">'.route('billing.account').'</bdi>', $html);
    }

    public function test_trial_expiring_reminder_renders_the_english_template_as_ltr(): void
    {
        $mailable = new TrialExpiringReminder(
            ownerName: 'Netanel',
            daysRemaining: 3,
            organizationName: 'Kalfa Test Organization',
            planName: 'Pro Trial',
            trialEndsAt: Carbon::parse('2026-03-25 10:00:00'),
            selectPlanUrl: route('billing.account'),
            mailLocale: 'en',
        );

        $mailable->assertHasSubject('Reminder: Your trial is ending soon');
        $mailable->assertSeeInHtml('Netanel');
        $mailable->assertSeeInHtml('Kalfa Test Organization');

        $html = $mailable->render();

        $this->assertStringContainsString('lang="en"', $html);
        $this->assertStringContainsString('dir="ltr"', $html);
        $this->assertStringContainsString('text-align:left', $html);
        $this->assertStringContainsString('<bdi dir="auto">Netanel</bdi>', $html);
        $this->assertStringContainsString('<bdi dir="auto">Kalfa Test Organization</bdi>', $html);
        $this->assertStringContainsString('<bdi dir="auto">Pro Trial</bdi>', $html);
    }
}
