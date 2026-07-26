@extends('layouts.app')

@section('title', 'Lead Details')

@section('content')
    <div id="lead-detail"></div>

    <hr>
    <h5>Assign Lead <span class="text-muted small">(manager only)</span></h5>
    <form id="assign-form" class="row g-2 mb-2" style="max-width: 400px;">
        <div class="col-8">
            <select class="form-select form-select-sm" id="assign-to">
                <option value="">Select Rep</option>
                @foreach ($reps as $rep)
                    <option value="{{ $rep->id }}">{{ $rep->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-4">
            <button class="btn btn-sm btn-secondary w-100">Assign</button>
        </div>
    </form>
    <div id="assign-error" class="alert alert-danger d-none"></div>

    <h5 class="mt-4">Update Status</h5>
    <form id="status-form" class="row g-2 mb-2" style="max-width: 400px;">
        <div class="col-8">
            <select class="form-select form-select-sm" id="status-select">
                <option value="new">New</option>
                <option value="contacted">Contacted</option>
                <option value="qualified">Qualified</option>
                <option value="won">Won</option>
                <option value="lost">Lost</option>
            </select>
        </div>
        <div class="col-4">
            <button class="btn btn-sm btn-secondary w-100">Update</button>
        </div>
    </form>
    <div id="status-error" class="alert alert-danger d-none"></div>

    <h5 class="mt-4">Activities</h5>
    <ul id="activities-list" class="list-group mb-3"></ul>

    <h6>Add Activity</h6>
    <form id="activity-form" class="row g-2" style="max-width: 550px;">
        <div class="col-3">
            <select class="form-select form-select-sm" id="activity-type">
                <option value="call">Call</option>
                <option value="email">Email</option>
                <option value="meeting">Meeting</option>
                <option value="note">Note</option>
            </select>
        </div>
        <div class="col-7">
            <input type="text" class="form-control form-control-sm" id="activity-body" placeholder="Details" required>
        </div>
        <div class="col-2">
            <button class="btn btn-sm btn-primary w-100">Add</button>
        </div>
    </form>
@endsection

@section('scripts')
<script>
    const leadId = {{ $id }};

    async function loadLead() {
        const result = await apiFetch(`/leads/${leadId}`);
        const lead = result.data;

        document.getElementById('lead-detail').innerHTML = `
            <h3>${lead.name} <span class="badge bg-secondary">${lead.status}</span></h3>
            <p class="text-muted">${lead.email} &middot; ${lead.phone} &middot; ${lead.company ?? 'No company'}</p>
            <p>Source: ${lead.source} &middot; Expected Value: ₹${Number(lead.expected_value).toLocaleString('en-IN')}</p>
            <p>Assigned Rep: ${lead.assigned_rep ? lead.assigned_rep.name : 'Unassigned'}</p>
        `;

        document.getElementById('status-select').value = lead.status;

        document.getElementById('activities-list').innerHTML = lead.activities.map(activity => `
            <li class="list-group-item">
                <strong>${activity.type}</strong> by ${activity.user.name} &mdash; ${activity.body}
                <div class="text-muted small">${new Date(activity.occurred_at).toLocaleString()}</div>
            </li>
        `).join('') || '<li class="list-group-item text-muted">No activities yet.</li>';
    }

    document.getElementById('assign-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const errorBox = document.getElementById('assign-error');
        errorBox.classList.add('d-none');

        try {
            await apiFetch(`/leads/${leadId}/assign`, {
                method: 'POST',
                body: JSON.stringify({ assigned_to: document.getElementById('assign-to').value }),
            });
            loadLead();
        } catch (err) {
            errorBox.textContent = err.body?.errors?.assigned_to?.[0] || err.message;
            errorBox.classList.remove('d-none');
        }
    });

    document.getElementById('status-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const statusSelect = document.getElementById('status-select');
        const errorBox = document.getElementById('status-error');
        errorBox.classList.add('d-none');
        statusSelect.classList.remove('is-invalid');

        try {
            await apiFetch(`/leads/${leadId}`, {
                method: 'PATCH',
                body: JSON.stringify({ status: statusSelect.value }),
            });
            loadLead();
        } catch (err) {
            errorBox.textContent = err.body?.errors?.status?.[0] || err.message;
            errorBox.classList.remove('d-none');
            statusSelect.classList.add('is-invalid');
            loadLead();
        }
    });

    document.getElementById('activity-form').addEventListener('submit', async (e) => {
        e.preventDefault();

        try {
            await apiFetch(`/leads/${leadId}/activities`, {
                method: 'POST',
                body: JSON.stringify({
                    type: document.getElementById('activity-type').value,
                    body: document.getElementById('activity-body').value,
                }),
            });
            document.getElementById('activity-body').value = '';
            loadLead();
        } catch (err) {
            alert(err.message);
        }
    });

    loadLead();
</script>
@endsection
