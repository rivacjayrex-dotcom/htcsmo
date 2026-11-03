<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class FeedbackModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_feedback_is_delivered_once_without_a_banner(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'smo']));
        $response = $this->withSession(['success' => 'Item has been removed from inventory.'])
            ->get(route('inventory.index'))->assertOk();

        $messages = $this->feedback($response->getContent());
        $this->assertSame([['tone' => 'success', 'message' => 'Item has been removed from inventory.']], $messages);
        $response->assertDontSee('alert alert-success', false);
    }

    public function test_errors_from_named_bags_are_included_and_html_is_encoded(): void
    {
        $errors = (new ViewErrorBag)
            ->put('default', new MessageBag(['name' => 'Name is required.']))
            ->put('updatePassword', new MessageBag(['password' => 'Password is too short.']));
        $response = $this->actingAs(User::factory()->create())
            ->withSession(['errors' => $errors, 'warning' => '</script><img src=x onerror=alert(1)>'])
            ->get(route('profile.edit'))->assertOk();

        $messages = $this->feedback($response->getContent());
        $this->assertSame(['Name is required.', 'Password is too short.'], $messages[0]['messages']);
        $this->assertSame('</script><img src=x onerror=alert(1)>', $messages[1]['message']);
        $response->assertDontSee('</script><img src=x onerror=alert(1)>', false)
            ->assertDontSee('Your password was not changed.');
    }

    public function test_guest_status_and_account_status_have_readable_modal_messages(): void
    {
        $guest = $this->withSession(['status' => 'A password reset link has been sent.'])
            ->get(route('login'))->assertOk();
        $this->assertSame('A password reset link has been sent.', $this->feedback($guest->getContent())[0]['message']);
        $guest->assertDontSee('alert alert-success', false);

        $account = $this->actingAs(User::factory()->create())
            ->withSession(['status' => 'profile-updated'])
            ->get(route('profile.edit'))->assertOk();
        $this->assertSame('Account information updated successfully.', $this->feedback($account->getContent())[0]['message']);
    }

    private function feedback(string $html): array
    {
        $document = new \DOMDocument;
        @$document->loadHTML($html);
        $nodes = (new \DOMXPath($document))->query('//script[@data-feedback-messages]');
        $this->assertSame(1, $nodes->length);

        return json_decode($nodes->item(0)->textContent, true, flags: JSON_THROW_ON_ERROR);
    }
}
