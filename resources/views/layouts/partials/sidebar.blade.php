<div class="quixnav">
    <div class="quixnav-scroll">
        <ul class="metismenu" id="menu">
            <li class="nav-label first">Main Menu</li>

            {{-- Admin-only --}}
            @if (Auth::user()->role === 'admin')
                <li><a href="{{ route('dashboard') }}"><i class="icon icon-single-04"></i><span class="nav-text">Admin
                            Dashboard</span></a></li>

                <li class="nav-label">Transactions</li>

                <li>
                    <a class="has-arrow" href="javascript:void()" aria-expanded="false">
                        <i class="fa fa-upload"></i><span class="nav-text">File Uploads</span>
                    </a>
                    <ul aria-expanded="false">
                        <li><a href="{{ route('documents') }}">File Datatable</a></li>
                    </ul>
                </li>

                <li>
                    <a class="has-arrow" href="javascript:void()" aria-expanded="false">
                        <i class="icon icon-chart-pie-36"></i><span class="nav-text">Master List</span>
                    </a>
                    <ul aria-expanded="false">
                        <li><a href="{{ route('master') }}">Master List Datatable</a></li>
                    </ul>
                </li>

                <li>
                    <a class="has-arrow" href="javascript:void()" aria-expanded="false">
                        <i class="icon icon-credit-card"></i><span class="nav-text">Billing</span>
                    </a>
                    <ul aria-expanded="false">
                        <li><a href="{{ route('billing') }}">Billing Datatable</a></li>
                    </ul>
                </li>

                <li>
                    <a class="has-arrow" href="javascript:void()" aria-expanded="false">
                        <i class="icon icon-wallet-90"></i><span class="nav-text">Remittance</span>
                    </a>
                    <ul aria-expanded="false">
                        <li><a href="{{ route('remittance') }}">Remittance Datatable</a></li>
                    </ul>
                </li>

                <li>
                    <a class="has-arrow" href="javascript:void()" aria-expanded="false">
                        <i class="icon icon-payment"></i><span class="nav-text">ATM</span>
                    </a>
                    <ul aria-expanded="false">
                        <li><a href="{{ route('atm') }}">ATM Module</a></li>
                    </ul>
                </li>

                <li>
                    <a class="has-arrow" href="javascript:void()" aria-expanded="false">
                        <i class="icon icon-wallet-90"></i><span class="nav-text">Special Billing</span>
                    </a>
                    <ul aria-expanded="false">
                        <li><a href="{{ route('special-billing.index') }}">Special Billing Datatable</a></li>
                    </ul>
                </li>

                <li>
                    <a class="has-arrow" href="javascript:void()" aria-expanded="false">
                        <i class="icon icon-chart-pie-36"></i><span class="nav-text">Configuration</span>
                    </a>
                    <ul aria-expanded="false">
                        <li><a href="{{ route('loans') }}">Loan Products</a></li>
                        <li><a href="{{ route('saving-products.index') }}">Saving Products</a></li>
                        <li><a href="{{ route('share-products.index') }}">Share Products</a></li>
                        <li><a href="#" data-toggle="modal" data-target="#pgbMembersUploadModal"></i>PGB Members File Upload</a></li>
                        <li><a href="#" data-toggle="modal" data-target="#savingsSharesUploadModal"></i>Savings & Shares Deduction File Upload</a></li>
                    </ul>
                </li>

                {{-- <li>
                    <a class="has-arrow" href="javascript:void()" aria-expanded="false">
                        <i class="icon icon-wallet-90"></i><span class="nav-text">Accounts</span>
                    </a>
                    <ul aria-expanded="false">
                        <li><a href="{{ route('savings') }}">Savings Accounts</a></li>
                        <li><a href="{{ route('shares') }}">Share Accounts</a></li>
                        <li><a href="{{ route('list') }}">Loan Accounts</a></li>
                    </ul>
                </li> --}}



                <li class="nav-label">Groups</li>

                <li>
                    <a class="has-arrow" href="javascript:void()" aria-expanded="false">
                        <i class="icon icon-pin-3"></i><span class="nav-text">Branch</span>
                    </a>
                    <ul aria-expanded="false">
                        <li><a href="{{ route('branch') }}">Branch Datatable</a></li>
                    </ul>
                </li>

                {{-- <li>
                    <a class="has-arrow" href="javascript:void()" aria-expanded="false">
                        <i class="icon icon-users-mm"></i><span class="nav-text">Members</span>
                    </a>
                    <ul aria-expanded="false">
                        <li><a href="{{ route('member') }}">Members Datatable</a></li>
                    </ul>
                </li> --}}

                <li>
                    <a href="{{ route('users') }}" aria-expanded="false">
                        <i class="icon icon-single-04"></i><span class="nav-text">Users</span>
                    </a>
                </li>
            @endif

            {{-- Branch-only --}}
            @if (Auth::user()->role === 'branch')
                <li><a href="{{ route('dashboard_branch') }}"><i class="icon icon-single-04"></i><span
                            class="nav-text">Branch Dashboard</span></a></li>

                <li class="nav-label">Transactions</li>

                <li>
                    <a class="has-arrow" href="javascript:void()" aria-expanded="false">
                        <i class="fa fa-upload"></i><span class="nav-text">File Uploads</span>
                    </a>
                    <ul aria-expanded="false">
                        <li><a href="{{ route('documents.branch') }}">File Datatable</a></li>
                    </ul>
                </li>

                  <li>
                    <a class="has-arrow" href="javascript:void()" aria-expanded="false">
                        <i class="icon icon-chart-pie-36"></i><span class="nav-text">Master List</span>
                    </a>
                    <ul aria-expanded="false">
                        <li><a href="{{ route('master.branch') }}">Master List Datatable</a></li>

                    </ul>
                </li>

                <li>
                    <a class="has-arrow" href="javascript:void()" aria-expanded="false">
                        <i class="icon icon-credit-card"></i><span class="nav-text">Billing</span>
                    </a>
                    <ul aria-expanded="false">
                        <li><a href="{{ route('billing.branch') }}">Billing Datatable</a></li>
                    </ul>
                </li>

                <li>
                    <a class="has-arrow" href="javascript:void()" aria-expanded="false">
                        <i class="icon icon-wallet-90"></i><span class="nav-text">Remittance</span>
                    </a>
                    <ul aria-expanded="false">
                        <li><a href="{{ route('branch.remittance.index') }}">Remittance Datatable</a></li>
                    </ul>
                </li>

                <li>
                    <a class="has-arrow" href="javascript:void()" aria-expanded="false">
                        <i class="icon icon-wallet-90"></i><span class="nav-text">ATM</span>
                    </a>
                    <ul aria-expanded="false">
                        <li><a href="{{ route('branch.atm') }}">ATM Datatable</a></li>
                    </ul>
                </li>

                <li>
                    <a class="has-arrow" href="javascript:void()" aria-expanded="false">
                        <i class="icon icon-wallet-90"></i><span class="nav-text">Special Billing</span>
                    </a>
                    <ul aria-expanded="false">
                        <li><a href="{{ route('special-billing.index.branch') }}">Special Billing Datatable</a></li>
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
                    <h5 class="modal-title"><i class="fa fa-upload me-2"></i>Savings & Shares Deduction File Upload</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">
                        Upload file with savings and shares product codes to set deduction amounts.<br>
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
</script>
