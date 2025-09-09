@php
use App\Models\BillingSetting;
@endphp

<div class="quixnav">
    <div class="quixnav-scroll">
        <ul class="metismenu" id="menu">
            <li class="nav-label first">Main Menu</li>

            {{-- Admin only (limited access) --}}
            @if (Auth::user()->role === 'admin')
                <li><a href="{{ route('dashboard') }}"><i class="bi bi-house"></i><span class="nav-text">Admin Dashboard</span></a></li>

                <li class="nav-label">Transactions</li>

                <li>
                    <a class="has-arrow" href="javascript:void()" aria-expanded="false">
                        <i class="bi bi-upload"></i><span class="nav-text">File Uploads</span>
                    </a>
                    <ul aria-expanded="false">
                        <li><a href="{{ route('documents') }}">File Datatable</a></li>
                    </ul>
                </li>

                <li>
                    <a class="has-arrow" href="javascript:void()" aria-expanded="false">
                        <i class="bi bi-list"></i><span class="nav-text">Master List</span>
                    </a>
                    <ul aria-expanded="false">
                        <li><a href="{{ route('master') }}">Master List Datatable</a></li>
                    </ul>
                </li>

                <li>
                    <a class="has-arrow" href="javascript:void()" aria-expanded="false">
                        <i class="bi bi-credit-card"></i><span class="nav-text">Billing</span>
                    </a>
                    <ul aria-expanded="false">
                        <li><a href="{{ route('billing') }}">Billing Datatable</a></li>
                    </ul>
                </li>

                <li>
                    <a class="has-arrow" href="javascript:void()" aria-expanded="false">
                        <i class="bi bi-wallet2"></i><span class="nav-text">Remittance</span>
                    </a>
                    <ul aria-expanded="false">
                        <li><a href="{{ route('remittance') }}">Remittance Datatable</a></li>
                    </ul>
                </li>

                <li>
                    <a class="has-arrow" href="javascript:void()" aria-expanded="false">
                        <i class="bi bi-cash"></i><span class="nav-text">ATM</span>
                    </a>
                    <ul aria-expanded="false">
                        <li><a href="{{ route('atm') }}">ATM Module</a></li>
                    </ul>
                </li>

                <li>
                    <a class="has-arrow" href="javascript:void()" aria-expanded="false">
                        <i class="bi bi-wallet2"></i><span class="nav-text">Special Billing</span>
                    </a>
                    <ul aria-expanded="false">
                        <li><a href="{{ route('special-billing.index') }}">Special Billing Datatable</a></li>
                    </ul>
                </li>
            @endif

            {{-- Admin-MSP only (full access) --}}
            @if (Auth::user()->role === 'admin-msp')
                <li><a href="{{ route('dashboard') }}"><i class="bi bi-house"></i><span class="nav-text">Admin-MSP Dashboard</span></a></li>

                <li class="nav-label">Transactions</li>

                <li>
                    <a class="has-arrow" href="javascript:void()" aria-expanded="false">
                        <i class="bi bi-upload"></i><span class="nav-text">File Uploads</span>
                    </a>
                    <ul aria-expanded="false">
                        <li><a href="{{ route('documents') }}">File Datatable</a></li>
                    </ul>
                </li>

                <li>
                    <a class="has-arrow" href="javascript:void()" aria-expanded="false">
                        <i class="bi bi-list"></i><span class="nav-text">Master List</span>
                    </a>
                    <ul aria-expanded="false">
                        <li><a href="{{ route('master') }}">Master List Datatable</a></li>
                    </ul>
                </li>

                <li>
                    <a class="has-arrow" href="javascript:void()" aria-expanded="false">
                        <i class="bi bi-credit-card"></i><span class="nav-text">Billing</span>
                    </a>
                    <ul aria-expanded="false">
                        <li><a href="{{ route('billing') }}">Billing Datatable</a></li>
                    </ul>
                </li>

                <li>
                    <a class="has-arrow" href="javascript:void()" aria-expanded="false">
                        <i class="bi bi-wallet2"></i><span class="nav-text">Remittance</span>
                    </a>
                    <ul aria-expanded="false">
                        <li><a href="{{ route('remittance') }}">Remittance Datatable</a></li>
                    </ul>
                </li>

                <li>
                    <a class="has-arrow" href="javascript:void()" aria-expanded="false">
                        <i class="bi bi-cash"></i><span class="nav-text">ATM</span>
                    </a>
                    <ul aria-expanded="false">
                        <li><a href="{{ route('atm') }}">ATM Module</a></li>
                    </ul>
                </li>

                <li>
                    <a class="has-arrow" href="javascript:void()" aria-expanded="false">
                        <i class="bi bi-wallet2"></i><span class="nav-text">Special Billing</span>
                    </a>
                    <ul aria-expanded="false">
                        <li><a href="{{ route('special-billing.index') }}">Special Billing Datatable</a></li>
                    </ul>
                </li>

                <li>
                    <a class="has-arrow" href="javascript:void()" aria-expanded="false">
                        <i class="bi bi-gear"></i><span class="nav-text">Configuration</span>
                    </a>
                    <ul aria-expanded="false">
                        <li><a href="{{ route('loans') }}">Loan Products</a></li>
                        <li><a href="{{ route('saving-products.index') }}">Saving Products</a></li>
                        <li><a href="{{ route('share-products.index') }}">Share Products</a></li>
                        <li><a href="{{ route('admin.contra') }}">Contra Account</a></li>
                        <li><a href="#" data-toggle="modal" data-target="#pgbMembersUploadModal"></i>PGB Members File Upload</a></li>
                        <li><a href="#" data-toggle="modal" data-target="#savingsSharesUploadModal"></i>Deduction Amount File Upload</a></li>
                        <li>
                            <a href="javascript:void()" onclick="showRetainDuesModal()">
                                <span class="nav-text">Retain Dues</span>
                                <span class="badge badge-{{ BillingSetting::getBoolean('retain_dues_on_billing_close') ? 'success' : 'secondary' }} ml-2" id="retainDuesBadge">
                                    {{ BillingSetting::getBoolean('retain_dues_on_billing_close') ? 'Retained' : 'Not Retained' }}
                                </span>
                            </a>
                        </li>
                    </ul>

                </li>

                <li class="nav-label">Groups</li>

                <li>
                    <a class="has-arrow" href="javascript:void()" aria-expanded="false">
                        <i class="bi bi-building"></i><span class="nav-text">Branch</span>
                    </a>
                    <ul aria-expanded="false">
                        <li><a href="{{ route('branch') }}">Branch Datatable</a></li>
                    </ul>
                </li>

                <li>
                    <a href="{{ route('users') }}" aria-expanded="false"><i class="bi bi-people"></i><span class="nav-text">Users</span></a>
                </li>


            @endif

            {{-- Branch-only --}}
            @if (Auth::user()->role === 'branch')
                <li><a href="{{ route('dashboard_branch') }}"><i class="bi bi-house"></i><span
                            class="nav-text">Branch Dashboard</span></a></li>

                <li class="nav-label">Transactions</li>

                {{-- <li>
                    <a class="has-arrow" href="javascript:void()" aria-expanded="false">
                        <i class="fa fa-upload"></i><span class="nav-text">File Uploads</span>
                    </a>
                    <ul aria-expanded="false">
                        <li><a href="{{ route('documents.branch') }}">File Datatable</a></li>
                    </ul>
                </li> --}}

                  <li>
                    <a class="has-arrow" href="javascript:void()" aria-expanded="false">
                        <i class="bi bi-list"></i><span class="nav-text">Master List</span>
                    </a>
                    <ul aria-expanded="false">
                        <li><a href="{{ route('master.branch') }}">Master List Datatable</a></li>

                    </ul>
                </li>

                <li>
                    <a class="has-arrow" href="javascript:void()" aria-expanded="false">
                        <i class="bi bi-credit-card"></i><span class="nav-text">Billing</span>
                    </a>
                    <ul aria-expanded="false">
                        <li><a href="{{ route('billing.branch') }}">Billing Datatable</a></li>
                    </ul>
                </li>

                <li>
                    <a class="has-arrow" href="javascript:void()" aria-expanded="false">
                        <i class="bi bi-wallet2"></i><span class="nav-text">Remittance</span>
                    </a>
                    <ul aria-expanded="false">
                        <li><a href="{{ route('branch.remittance.index') }}">Remittance Datatable</a></li>
                    </ul>
                </li>

                <li>
                    <a class="has-arrow" href="javascript:void()" aria-expanded="false">
                        <i class="bi bi-cash"></i><span class="nav-text">ATM</span>
                    </a>
                    <ul aria-expanded="false">
                        <li><a href="{{ route('branch.atm') }}">ATM Datatable</a></li>
                    </ul>
                </li>

                <li>
                    <a class="has-arrow" href="javascript:void()" aria-expanded="false">
                        <i class="bi bi-wallet2"></i><span class="nav-text">Special Billing</span>
                    </a>
                    <ul aria-expanded="false">
                        <li><a href="{{ route('special-billing.index.branch') }}">Special Billing Datatable</a></li>
                    </ul>
                </li>
                <li>
                    <a class="has-arrow" href="javascript:void()" aria-expanded="false">
                        <i class="bi bi-file-earmark-text"></i><span class="nav-text">Reports</span>
                    </a>
                    <ul aria-expanded="false">
                        <li class="nav-label">Masterlist</li>
                        <li><a href="{{ route('master.exportMembersNoRegularSavingsBranch') }}">
                         <span class="d-none d-md-inline">No Regular Savings</span>
                        </a></li>
                        <li> <a href="{{ route('master.exportMemberDetailsBranch') }}">
                            <span class="d-none d-md-inline">Member Details</span>
                        </a></li>
                        <li class="nav-label">Billing</li>
                        <li><a href="{{ route('billing.exports.branch') }}">Export History</a></li>

                        <li class="nav-label">Remittance</li>
                        <li><a href="{{ route('branchRemittance.exportConsolidated') }}">Member Not Processed</a></li>
                        <li><a href="{{ route('branch.remittance.exportPerRemittanceSummaryRegular') }}">Summary (Regular)</a></li>
                        <li><a href="{{ route('branch.remittance.exportPerRemittanceSummarySpecial') }}">Summary (Special)</a></li>


                        <li class="nav-label">Archives</li>
                        <li> <a href="{{ route('billing.exports') }}">
                            Previous Billing Reports
                        </a></li>
                    </ul>
                </li>

            @endif

            {{-- Reports Section (Admin and Admin-MSP) --}}
            @if (Auth::user()->role === 'admin' || Auth::user()->role === 'admin-msp')
                <li>
                    <a class="has-arrow" href="javascript:void()" aria-expanded="false">
                        <i class="bi bi-file-earmark-text"></i><span class="nav-text">Reports</span>
                    </a>
                    <ul aria-expanded="false">
                        <li class="nav-label">Masterlist</li>
                        <li><a href="{{ route('master.exportMembersNoRegularSavings') }}">
                         <span class="d-none d-md-inline">No Regular Savings</span>
                        </a></li>
                        <li> <a href="{{ route('billing.members-no-branch') }}">
                            Members No Branch
                        </a></li>
                        <li> <a href="{{ route('master.exportMemberDetails') }}">
                            <span class="d-none d-md-inline">Member Details</span>
                        </a></li>

                        <li class="nav-label">Billing</li>
                        <li><a href="{{ route('billing.exportMemberDeductionDetails') }}">Member Deduction Details</a></li>

                        <li class="nav-label">Remittance</li>
                        <li><a href="{{ route('remittance.exportPerRemittance') }}">Full Per Remittance Report</a></li>
                        <li><a href="{{ route('remittance.exportPerRemittanceSummaryRegular') }}">Summary (Regular)</a></li>
                        <li><a href="{{ route('remittance.exportPerRemittanceSummarySpecial') }}">Summary (Special)</a></li>
                        <li><a href="{{ route('remittance.exportPerRemittanceLoans') }}">Loans Breakdown</a></li>
                        <li><a href="{{ route('remittance.exportPerRemittanceSavings') }}">Savings Breakdown</a></li>
                        <li><a href="{{ route('remittance.exportPerRemittanceShares') }}">Shares Breakdown</a></li>
                        <li><a href="{{ route('atm.export.list-of-profile') }}">List of Profile</a></li>
                        <li><a href="{{ route('atm.export.remittance-report-consolidated') }}">Remittance Report Consolidated</a></li>
                        <li><a href="{{ route('atm.export.remittance-report-per-branch') }}">Remittance Report Per Branch</a></li>
                        <li><a href="{{ route('atm.export.remittance-report-per-branch-member') }}">Remittance Report Per Branch Member</a></li>

                        <li class="nav-label">Archives</li>
                        <li> <a href="{{ route('billing.exports') }}">
                            Previous Billing Reports
                        </a></li>
                    </ul>
                </li>
            @endif

        </ul>
    </div>
</div>

{{-- Modals for sidebar uploads --}}
<div class="modal fade" id="pgbMembersUploadModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <form action="{{ route('master.upload.coreid') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fa fa-upload me-2"></i>PGB Members File Upload</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">
                        Upload CoreID file to set member tagging to PGB.<br>
                        File should have "Customer No" header in A1 and CID values below.<br>
                        CIDs will be padded to 9 digits (e.g., 123 becomes 000000123).
                    </p>
                    <input type="file" name="coreid_file" class="form-control-file" accept=".xlsx,.xls,.csv" required>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-warning">
                        <i class="fa fa-upload me-1"></i>Upload PGB Members
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="savingsSharesUploadModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <form action="{{ route('master.upload.savings-shares-product') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fa fa-upload me-2"></i>Savings, Shares & Loans Deduction File Upload</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">
                        Upload file with savings, shares, and loan product codes to set deduction amounts.<br>
                        File should have "CoreID" header in A1, product codes in row 1 (columns B onwards),<br>
                        and deduction amounts below. CIDs will be padded to 9 digits.
                    </p>
                    <input type="file" name="savings_shares_file" class="form-control-file" accept=".xlsx,.xls,.csv" required>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-info">
                        <i class="fa fa-upload me-1"></i>Upload Products
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // PGB Members Upload
    const pgbForm = document.querySelector('#pgbMembersUploadModal form');
    if (pgbForm) {
        pgbForm.addEventListener('submit', function (e) {
            Swal.fire({
                title: 'Uploading... Please wait',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });
        });
    }

    // Savings & Shares Deduction Upload
    const savingsSharesForm = document.querySelector('#savingsSharesUploadModal form');
    if (savingsSharesForm) {
        savingsSharesForm.addEventListener('submit', function (e) {
            Swal.fire({
                title: 'Uploading... Please wait',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });
        });
    }

    // Optionally, handle server response via flash session or AJAX for feedback
    // Example for Laravel flash session (if used):
    @if(session('success'))
        Swal.fire('Success', @json(session('success')), 'success');
    @elseif(session('error'))
        Swal.fire('Error', @json(session('error')), 'error');
    @endif
});

// Show retain dues confirmation modal
function showRetainDuesModal() {
    const currentStatus = document.getElementById('retainDuesBadge').textContent.trim();
    const isCurrentlyRetained = currentStatus === 'Retained';

    const newStatus = isCurrentlyRetained ? 'Not Retained' : 'Retained';
    const actionText = isCurrentlyRetained ? 'disable' : 'enable';

    Swal.fire({
        title: 'Retain Dues Setting',
        html: `
            <div class="text-center">
                <p>Current Status: <strong>${currentStatus}</strong></p>
                <p>Do you want to <strong>${actionText}</strong> retaining dues on billing close?</p>
                <p class="text-muted small">New Status: <strong>${newStatus}</strong></p>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, Confirm',
        cancelButtonText: 'Cancel',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            toggleRetainDues();
        }
    });
}

// Toggle retain dues setting
function toggleRetainDues() {
    fetch('{{ route("billing.toggle-retain-dues") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const badge = document.getElementById('retainDuesBadge');
            if (data.retain_dues) {
                badge.textContent = 'Retained';
                badge.className = 'badge badge-success ml-2';
            } else {
                badge.textContent = 'Not Retained';
                badge.className = 'badge badge-secondary ml-2';
            }

            Swal.fire({
                title: 'Setting Updated',
                text: data.message,
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });
        } else {
            Swal.fire('Error', data.message || 'Failed to update setting', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire('Error', 'Failed to update setting', 'error');
    });
}
</script>
