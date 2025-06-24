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
                        <i class="icon icon-chart-pie-36"></i><span class="nav-text">Products</span>
                    </a>
                    <ul aria-expanded="false">
                        <li><a href="{{ route('loans') }}">Loan Products</a></li>
                        <li><a href="{{ route('saving-products.index') }}">Saving Products</a></li>
                        <li><a href="{{ route('share-products.index') }}">Share Products</a></li>
                    </ul>
                </li>

                <li>
                    <a class="has-arrow" href="javascript:void()" aria-expanded="false">
                        <i class="icon icon-wallet-90"></i><span class="nav-text">Accounts</span>
                    </a>
                    <ul aria-expanded="false">
                        <li><a href="{{ route('savings') }}">Savings Accounts</a></li>
                        <li><a href="{{ route('shares') }}">Share Accounts</a></li>
                        <li><a href="{{ route('list') }}">Loan Accounts</a></li>
                    </ul>
                </li>



                <li class="nav-label">Groups</li>

                <li>
                    <a class="has-arrow" href="javascript:void()" aria-expanded="false">
                        <i class="icon icon-pin-3"></i><span class="nav-text">Branch</span>
                    </a>
                    <ul aria-expanded="false">
                        <li><a href="{{ route('branch') }}">Branch Datatable</a></li>
                    </ul>
                </li>

                <li>
                    <a class="has-arrow" href="javascript:void()" aria-expanded="false">
                        <i class="icon icon-users-mm"></i><span class="nav-text">Members</span>
                    </a>
                    <ul aria-expanded="false">
                        <li><a href="{{ route('member') }}">Members Datatable</a></li>
                    </ul>
                </li>

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
