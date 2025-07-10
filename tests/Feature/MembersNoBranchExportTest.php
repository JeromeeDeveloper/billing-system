<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Member;
use App\Models\User;
use App\Exports\MembersNoBranchExport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;

class MembersNoBranchExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_members_no_branch_export_returns_correct_data()
    {
        // Create a member with no branch
        $memberNoBranch = Member::factory()->create([
            'branch_id' => null,
            'fname' => 'John',
            'lname' => 'Doe',
            'emp_id' => 'EMP001'
        ]);

        // Create a member with a branch
        $memberWithBranch = Member::factory()->create([
            'branch_id' => 1,
            'fname' => 'Jane',
            'lname' => 'Smith',
            'emp_id' => 'EMP002'
        ]);

        $export = new MembersNoBranchExport();
        $collection = $export->collection();

        // Should only include members with no branch
        $this->assertEquals(1, $collection->count());
        $this->assertEquals('EMP001', $collection->first()->emp_id);
        $this->assertEquals('John', $collection->first()->fname);
        $this->assertEquals('Doe', $collection->first()->lname);
    }

    public function test_members_no_branch_export_has_correct_headings()
    {
        $export = new MembersNoBranchExport();
        $headings = $export->headings();

        $expectedHeadings = [
            'Employee ID',
            'First Name',
            'Last Name',
            'Full Name',
            'Address',
            'Birth Date',
            'Gender',
            'Customer Type',
            'Customer Classification',
            'Occupation',
            'Industry',
            'Area Officer',
            'Area',
            'Account Name',
            'Status',
            'Approval No',
            'Start Hold',
            'Expiry Date',
            'Account Status',
            'Date Registered',
            'Member Tagging',
            'Savings Balance',
            'Share Balance',
            'Loan Balance',
            'Principal',
            'Regular Principal',
            'Special Principal',
            'Start Date',
            'End Date',
            'Billing Period'
        ];

        $this->assertEquals($expectedHeadings, $headings);
    }

    public function test_members_no_branch_export_route_is_accessible_by_admin()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->get('/billing/members-no-branch');

        $response->assertStatus(200);
    }

    public function test_members_no_branch_export_route_is_not_accessible_by_branch_user()
    {
        $branchUser = User::factory()->create(['role' => 'branch']);

        $response = $this->actingAs($branchUser)
            ->get('/billing/members-no-branch');

        $response->assertStatus(403);
    }
}
