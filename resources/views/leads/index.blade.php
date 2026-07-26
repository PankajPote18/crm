@extends('layouts.app')

@section('title', 'Leads')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Leads</h3>
        <a href="/leads/create" class="btn btn-primary btn-sm">+ New Lead</a>
    </div>

    <div class="row g-2 mb-3">
        <div class="col-auto">
            <input type="text" id="search" class="form-control form-control-sm" placeholder="Search name / email / company">
        </div>
        <div class="col-auto">
            <select id="filter-status" class="form-select form-select-sm">
                <option value="">All statuses</option>
                <option value="new">New</option>
                <option value="contacted">Contacted</option>
                <option value="qualified">Qualified</option>
                <option value="won">Won</option>
                <option value="lost">Lost</option>
            </select>
        </div>
        <div class="col-auto">
            <select id="filter-source" class="form-select form-select-sm">
                <option value="">All sources</option>
                <option value="web">Web</option>
                <option value="referral">Referral</option>
                <option value="cold_call">Cold Call</option>
                <option value="event">Event</option>
                <option value="other">Other</option>
            </select>
        </div>
        <div class="col-auto">
            <select id="sort-by" class="form-select form-select-sm">
                <option value="created_at">Sort: Newest</option>
                <option value="expected_value">Sort: Expected Value</option>
            </select>
        </div>
        <div class="col-auto">
            <button class="btn btn-sm btn-secondary" onclick="loadLeads(1)">Apply</button>
        </div>
    </div>

    <table class="table table-sm table-striped">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Company</th>
                <th>Source</th>
                <th>Status</th>
                <th>Expected Value</th>
                <th>Assigned Rep</th>
                <th></th>
            </tr>
        </thead>
        <tbody id="leads-body"></tbody>
    </table>

    <nav><ul class="pagination" id="pagination"></ul></nav>
@endsection

@section('scripts')
<script>
    async function loadLeads(page = 1) {
        const params = new URLSearchParams({
            page,
            search: document.getElementById('search').value,
            status: document.getElementById('filter-status').value,
            source: document.getElementById('filter-source').value,
            sort_by: document.getElementById('sort-by').value,
            sort_dir: 'desc',
        });

        const result = await apiFetch('/leads?' + params.toString());
        const tbody = document.getElementById('leads-body');

        tbody.innerHTML = result.data.leads.map(lead => `
            <tr>
                <td>${lead.name}</td>
                <td>${lead.email}</td>
                <td>${lead.company ?? '-'}</td>
                <td>${lead.source}</td>
                <td><span class="badge bg-secondary">${lead.status}</span></td>
                <td>₹${Number(lead.expected_value).toLocaleString('en-IN')}</td>
                <td>${lead.assigned_rep ? lead.assigned_rep.name : '-'}</td>
                <td><a href="/leads/${lead.id}" class="btn btn-sm btn-outline-primary">View</a></td>
            </tr>
        `).join('') || '<tr><td colspan="8" class="text-muted">No leads found.</td></tr>';

        renderPagination(result.data.pagination);
    }

    function renderPagination(pagination) {
        const el = document.getElementById('pagination');
        let html = '';
        for (let i = 1; i <= pagination.last_page; i++) {
            html += `<li class="page-item ${i === pagination.current_page ? 'active' : ''}">
                <a class="page-link" href="#" onclick="loadLeads(${i}); return false;">${i}</a>
            </li>`;
        }
        el.innerHTML = html;
    }

    loadLeads();
</script>
@endsection
