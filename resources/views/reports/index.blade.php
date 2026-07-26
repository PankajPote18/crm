@extends('layouts.app')

@section('title', 'Rep Performance Report')

@section('content')
    <h3>Rep Performance Report</h3>
    <table class="table table-sm table-striped">
        <thead>
            <tr>
                <th>Rep</th>
                <th>Total Leads</th>
                <th>New</th>
                <th>Contacted</th>
                <th>Qualified</th>
                <th>Won</th>
                <th>Lost</th>
                <th>Total Expected Value</th>
                <th>Won Expected Value</th>
                <th>Activity Count</th>
            </tr>
        </thead>
        <tbody id="report-body"></tbody>
    </table>
@endsection

@section('scripts')
<script>
    async function loadReport() {
        const result = await apiFetch('/reports/rep-performance');

        document.getElementById('report-body').innerHTML = result.data.map(rep => `
            <tr>
                <td>${rep.rep_name}</td>
                <td>${rep.total_leads}</td>
                <td>${rep.leads_by_status.new}</td>
                <td>${rep.leads_by_status.contacted}</td>
                <td>${rep.leads_by_status.qualified}</td>
                <td>${rep.leads_by_status.won}</td>
                <td>${rep.leads_by_status.lost}</td>
                <td>$${Number(rep.total_expected_value).toLocaleString()}</td>
                <td>$${Number(rep.won_expected_value).toLocaleString()}</td>
                <td>${rep.activity_count}</td>
            </tr>
        `).join('') || '<tr><td colspan="10" class="text-muted">No data.</td></tr>';
    }

    loadReport();
</script>
@endsection
