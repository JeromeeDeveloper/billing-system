<div class="quixnav">
    <div class="quixnav-scroll">
        <ul class="metismenu" id="menu">
            <li class="nav-label first">Main Menu</li>
            <li>
                <a href="{{ route('dashboard') }}" aria-expanded="false">
                    <i class="icon icon-single-04"></i><span class="nav-text">Dashboard</span>
                </a>
            </li>

            <li class="nav-label">Transactions</li>

            <li>
                <a class="has-arrow" href="javascript:void()" aria-expanded="false">
                    <i class="fa fa-upload"></i><span class="nav-text">File Uploads</span>
                </a>
                <ul aria-expanded="false">
                    <li><a href="{{route('documents')}}">File Datatable</a></li>
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
                    <i class="icon icon-chart-pie-36"></i><span class="nav-text">Loans Product</span>
                </a>
                <ul aria-expanded="false">
                    <li><a href="{{route('loans')}}">Loans Products List</a></li>
                    <li><a href="{{route('list')}}">Member Loans</a></li>
                </ul>
            </li>
            <li>
                <a class="has-arrow" href="javascript:void()" aria-expanded="false">
                    <i class="icon icon-payment"></i><span class="nav-text">Atm</span>
                </a>
                <ul aria-expanded="false">
                    <li><a href="{{ route('atm') }}">Atm Module</a></li>
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
                <a href="{{route('users')}}" aria-expanded="false">
                    <i></i><span class="nav-text">Users</span>
                </a>
            </li>
        </ul>
    </div>
</div>
