@extends('layouts.app')

@section('title', 'New Lead')

@section('content')
    <style>
        /* Hide the number input's spinner arrows, keep decimal entry via keyboard */
        #expected_value::-webkit-outer-spin-button,
        #expected_value::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        #expected_value {
            -moz-appearance: textfield;
        }
    </style>

    <h3>New Lead</h3>
    <div id="error" class="alert alert-danger d-none"></div>

    <form id="lead-form" class="row g-3" style="max-width: 600px;" novalidate>
        <div class="col-12">
            <label class="form-label">Name</label>
            <input type="text" class="form-control" id="name" required>
            <div class="invalid-feedback" id="name-error"></div>
        </div>
        <div class="col-12">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" id="email" required>
            <div class="invalid-feedback" id="email-error"></div>
        </div>
        <div class="col-12">
            <label class="form-label">Phone</label>
            <input type="text" class="form-control" id="phone" required>
            <div class="invalid-feedback" id="phone-error"></div>
        </div>
        <div class="col-12">
            <label class="form-label">Company</label>
            <input type="text" class="form-control" id="company">
            <div class="invalid-feedback" id="company-error"></div>
        </div>
        <div class="col-6">
            <label class="form-label">Source</label>
            <select class="form-select" id="source" required>
                <option value="web">Web</option>
                <option value="referral">Referral</option>
                <option value="cold_call">Cold Call</option>
                <option value="event">Event</option>
                <option value="other">Other</option>
            </select>
            <div class="invalid-feedback" id="source-error"></div>
        </div>
        <div class="col-6">
            <label class="form-label">Expected Value</label>
            <input type="number" step="0.01" min="0" class="form-control" id="expected_value" required>
            <div class="invalid-feedback" id="expected_value-error"></div>
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-primary">Create Lead</button>
        </div>
    </form>
@endsection

@section('scripts')
<script>
    const leadFields = ['name', 'email', 'phone', 'company', 'source', 'expected_value'];

    function clearLeadErrors() {
        document.getElementById('error').classList.add('d-none');
        leadFields.forEach((field) => {
            document.getElementById(field).classList.remove('is-invalid');
            document.getElementById(field + '-error').textContent = '';
        });
    }

    function showLeadFieldErrors(errors) {
        Object.keys(errors).forEach((field) => {
            const input = document.getElementById(field);
            const feedback = document.getElementById(field + '-error');
            if (input && feedback) {
                input.classList.add('is-invalid');
                feedback.textContent = errors[field][0];
            }
        });
    }

    document.getElementById('lead-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        clearLeadErrors();

        try {
            const result = await apiFetch('/leads', {
                method: 'POST',
                body: JSON.stringify({
                    name: document.getElementById('name').value,
                    email: document.getElementById('email').value,
                    phone: document.getElementById('phone').value,
                    company: document.getElementById('company').value || null,
                    source: document.getElementById('source').value,
                    expected_value: document.getElementById('expected_value').value,
                }),
            });
            window.location.href = `/leads/${result.data.id}`;
        } catch (err) {
            if (err.status === 422 && err.body?.errors) {
                showLeadFieldErrors(err.body.errors);
            } else {
                const errorBox = document.getElementById('error');
                errorBox.textContent = err.message;
                errorBox.classList.remove('d-none');
            }
        }
    });
</script>
@endsection
