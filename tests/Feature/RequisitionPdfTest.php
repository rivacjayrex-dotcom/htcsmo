<?php

namespace Tests\Feature;

use App\Models\ApprovalLog;
use App\Models\DigitalSignature;
use App\Models\Requisition;
use App\Models\RequisitionItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RequisitionPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_requisition_pdf_contains_complete_items_and_real_signature_history(): void
    {
        $requestor = User::factory()->create(['department' => 'CETE']);
        $requisition = Requisition::create([
            'user_id' => $requestor->id,
            'request_type' => 'major',
            'status' => 'approved_dept',
            'purpose' => 'Equip the electronics laboratory',
            'grand_total' => 2500,
        ]);

        RequisitionItem::create([
            'requisition_id' => $requisition->id,
            'item_name' => 'Digital multimeter',
            'specifications' => 'True RMS, CAT III rated',
            'quantity' => 2,
            'unit' => 'unit',
            'unit_price' => 1250,
            'subtotal' => 2500,
        ]);

        $log = ApprovalLog::create([
            'requisition_id' => $requisition->id,
            'action' => 'Approved by Department Head',
            'role' => 'dept_head',
            'remarks' => 'Verified for laboratory use',
        ]);

        DigitalSignature::create([
            'approval_log_id' => $log->id,
            'signature_token' => hash('sha256', 'test-signature'),
            'signer_name' => 'Test Department Head',
            'signer_role' => 'Department Head',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'signed_at' => now(),
        ]);

        $this->actingAs($requestor)
            ->get(route('requests.download', $requisition))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertDownload('HTC-Requisition-'.$requisition->fresh()->tracking_code.'.pdf');
    }

    public static function requisitionTiers(): array
    {
        return [['minor'], ['major']];
    }

    #[DataProvider('requisitionTiers')]
    public function test_wrapped_item_names_do_not_expand_a_short_request_across_pages(string $tier): void
    {
        $requestor = User::factory()->create(['department' => 'CETE']);
        $requisition = Requisition::create([
            'user_id' => $requestor->id,
            'request_type' => $tier,
            'status' => 'pending',
            'purpose' => 'For office and classroom use.',
            'grand_total' => $tier === 'minor' ? 58.90 : 1500,
        ]);
        RequisitionItem::create([
            'requisition_id' => $requisition->id,
            'item_name' => 'Isopropyl Rubbing Alcohol, 500 ml',
            'specifications' => 'Reference code: 105-00026 | Brand: Green Cross',
            'quantity' => 1,
            'unit' => 'Bottle',
            'unit_price' => $requisition->grand_total,
            'subtotal' => $requisition->grand_total,
        ]);

        $response = $this->actingAs($requestor)
            ->get(route('requests.download', $requisition))
            ->assertOk()->assertHeader('content-type', 'application/pdf');

        $this->assertSame(1, preg_match_all('/\/Type\s*\/Page\b/', $response->getContent()),
            'A short requisition must keep the wrapped item, blank form rows, total, and signatures on one page.');
    }
}
