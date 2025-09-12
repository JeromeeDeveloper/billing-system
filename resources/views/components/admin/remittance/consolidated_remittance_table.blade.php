<div class="container-fluid">
    <div class="card mb-4">
        <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center py-3">
            <div class="d-flex align-items-center">
                <i class="fa fa-chart-bar me-2"></i>
                <h5 class="mb-0 fw-bold">Consolidated Remittance Report</h5>
            </div>
            <div class="d-flex gap-3 align-items-center">
                <div class="d-flex gap-2">
                    <select id="sectionFilter" class="form-select form-select-sm border-0 bg-white bg-opacity-90"
                        style="width: 160px; display: none;">
                        <option value="summary">Summary</option>
                    </select>
                    <input type="text" id="searchFilter"
                        class="form-control form-control-sm border-0 bg-white bg-opacity-90"
                        placeholder="Search members..." style="width: 180px;">
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('remittance.exportUnmatchedMembers') }}" class="btn btn-light btn-sm shadow-sm">
                        <i class="fa fa-file-excel-o text-info me-1"></i> Members not processed
                    </a>
                    {{-- <a href="{{ route('remittance.exportPerRemittance') }}" class="btn btn-light btn-sm shadow-sm">
						<i class="fa fa-file-excel-o text-warning me-1"></i> Per-Remittance Report
                    </a> --}}
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered text-center" id="consolidatedTable">
                    <thead id="tableHeaders">
                        <!-- Headers will be dynamically generated based on section -->
                    </thead>
                    <tbody id="tableBody">
                        <!-- Data will be dynamically generated based on section -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const sectionFilter = document.getElementById('sectionFilter');
        const searchFilter = document.getElementById('searchFilter');
        const tableHeaders = document.getElementById('tableHeaders');
        const tableBody = document.getElementById('tableBody');

        // Get remittance data from PHP
        const remittanceData = @json($remittanceData ?? []);
        const remittanceTags = @json($remittanceTags ?? []);

        function generateHeaders(section) {
            let headers = [];

            switch (section) {
                case 'summary':
                    headers = ['CID', 'Member Name', 'Type', 'Remitted Loans', 'Remitted Savings',
                        'Remitted Shares', 'Total Remitted'
                    ];
                    break;
                case 'loans':
                    headers = ['CID', 'Member Name', 'Type', 'Billed Amount'];
                    remittanceTags.forEach(tag => {
                        headers.push(`Remittance Loans ${tag}`);
                    });
                    headers.push('Running Balance');
                    break;
                case 'savings':
                    headers = ['CID', 'Member Name', 'Type'];
                    remittanceTags.forEach(tag => {
                        headers.push(`Remittance Savings ${tag}`);
                    });
                    headers.push('Total Remittance on Savings');
                    break;
                case 'shares':
                    headers = ['CID', 'Member Name', 'Type'];
                    remittanceTags.forEach(tag => {
                        headers.push(`Remittance Share ${tag}`);
                    });
                    headers.push('Total Remittance on Share');
                    break;
                default:
                    headers = ['CID', 'Member Name', 'Type', 'Remitted Loans', 'Remitted Savings',
                        'Remitted Shares', 'Total Remitted'
                    ];
            }

            tableHeaders.innerHTML = `<tr>${headers.map(h => `<th>${h}</th>`).join('')}</tr>`;
        }

        function generateTableData(section) {
            let rows = [];

            console.log('Generating table data for section:', section);

            // Group data by CID
            const groupedData = {};
            remittanceData.forEach(item => {
                if (item && item.cid) {
                    if (!groupedData[item.cid]) {
                        groupedData[item.cid] = [];
                    }
                    groupedData[item.cid].push(item);
                }
            });

            console.log('Grouped data keys:', Object.keys(groupedData).length);

            Object.keys(groupedData).forEach(cid => {
                const memberReports = groupedData[cid];
                const memberName = memberReports[0].member_name || '';

                switch (section) {
                    case 'summary':
                        const totalLoans = memberReports.filter(r => r.remittance_type ===
                            'loans_savings').reduce((sum, r) => sum + parseFloat(r.remitted_loans ||
                            0), 0);
                        const totalSavings = memberReports.filter(r => r.remittance_type ===
                            'loans_savings').reduce((sum, r) => sum + parseFloat(r
                            .remitted_savings || 0), 0);
                        const totalShares = memberReports.filter(r => r.remittance_type === 'shares')
                            .reduce((sum, r) => sum + parseFloat(r.remitted_shares || 0), 0);
                        const totalRemitted = totalLoans + totalSavings + totalShares;

                        // Get billing type from the first report (they should all be the same for a member)
                        const billingType = memberReports.length > 0 ? (memberReports[0].billing_type ||
                            'Regular') : 'Regular';

                        console.log(
                            `Member ${cid}: loans=${totalLoans}, savings=${totalSavings}, shares=${totalShares}, total=${totalRemitted}, type=${billingType}`
                            );

                        if (totalRemitted > 0) {
                            rows.push([cid, memberName, billingType, totalLoans, totalSavings,
                                totalShares, totalRemitted
                            ]);
                        }
                        break;

                    case 'loans':
                        const loansReports = memberReports.filter(r => r.remittance_type ===
                            'loans_savings');
                        const totalLoansAmount = loansReports.reduce((sum, r) => sum + parseFloat(r
                            .remitted_loans || 0), 0);
                        const billedAmount = loansReports.reduce((sum, r) => sum + parseFloat(r
                            .billed_amount || 0), 0);

                        if (totalLoansAmount > 0) {
                            let row = [cid, memberName, 'Loans', billedAmount];
                            let totalPaid = 0;

                            remittanceTags.forEach(tag => {
                                const report = loansReports.find(r => r.remittance_tag == tag);
                                const amount = report ? parseFloat(report.remitted_loans || 0) :
                                    0;
                                row.push(amount);
                                totalPaid += amount;
                            });

                            row.push(billedAmount - totalPaid);
                            rows.push(row);
                        }
                        break;

                    case 'savings':
                        const savingsReports = memberReports.filter(r => r.remittance_type ===
                            'loans_savings');
                        const totalSavingsAmount = savingsReports.reduce((sum, r) => sum + parseFloat(r
                            .remitted_savings || 0), 0);

                        if (totalSavingsAmount > 0) {
                            let row = [cid, memberName, 'Savings'];
                            let totalPaid = 0;

                            remittanceTags.forEach(tag => {
                                const report = savingsReports.find(r => r.remittance_tag ==
                                tag);
                                const amount = report ? parseFloat(report.remitted_savings ||
                                    0) : 0;
                                row.push(amount);
                                totalPaid += amount;
                            });

                            row.push(totalPaid);
                            rows.push(row);
                        }
                        break;

                    case 'shares':
                        const sharesReports = memberReports.filter(r => r.remittance_type === 'shares');
                        const totalSharesAmount = sharesReports.reduce((sum, r) => sum + parseFloat(r
                            .remitted_shares || 0), 0);

                        if (totalSharesAmount > 0) {
                            let row = [cid, memberName, 'Shares'];
                            let totalPaid = 0;

                            remittanceTags.forEach(tag => {
                                const report = sharesReports.find(r => r.remittance_tag == tag);
                                const amount = report ? parseFloat(report.remitted_shares ||
                                    0) : 0;
                                row.push(amount);
                                totalPaid += amount;
                            });

                            row.push(totalPaid);
                            rows.push(row);
                        }
                        break;
                }
            });

            // Filter by search term
            const searchTerm = searchFilter.value.toLowerCase();
            if (searchTerm) {
                rows = rows.filter(row => row[1].toLowerCase().includes(searchTerm));
            }

            // Generate table rows
            if (rows.length === 0) {
                const colCount = document.querySelectorAll('#tableHeaders th').length;
                tableBody.innerHTML =
                    `<tr><td colspan="${colCount}" class="text-center text-muted">No data available for the selected section and filters.</td></tr>`;
            } else {
                tableBody.innerHTML = rows.map(row =>
                    `<tr class="data-row">${row.map(cell => `<td>${typeof cell === 'number' ? cell.toLocaleString() : cell}</td>`).join('')}</tr>`
                ).join('');
            }

            console.log('Generated rows:', rows);
        }

        function updateTable() {
            const section = sectionFilter.value;
            generateHeaders(section);
            generateTableData(section);
        }

        // Event listeners
        sectionFilter.addEventListener('change', updateTable);
        searchFilter.addEventListener('input', updateTable);

        // Initial load
        // Test if we have data
        if (remittanceData.length === 0) {
            // Still generate headers
            generateHeaders('summary');
            tableBody.innerHTML =
                '<tr><td colspan="7" class="text-center text-muted">No remittance data available. Please upload remittance files first.</td></tr>';
        } else {
            updateTable();
        }
    });
</script>
