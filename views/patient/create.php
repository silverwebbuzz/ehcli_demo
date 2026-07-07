<?php
ob_start();
$page_title = 'Add Patient - Dr. Feelgood';
?>

<!-- PAGE HEADER -->
<div class="page-header">
    <h1 class="page-title">
        <i class="fas fa-user-plus"></i> Add New Patient
    </h1>
</div>

<!-- Inline error banner (hidden until a create attempt fails) -->
<div id="createPatientError" style="display:none;align-items:center;gap:10px;background:#fef2f2;color:#b91c1c;border:1px solid #fca5a5;border-radius:8px;padding:12px 16px;margin-bottom:16px;font-size:0.95rem;">
    <i class="fas fa-exclamation-circle"></i>
    <span id="createPatientErrorMsg" style="flex:1;"></span>
    <span onclick="document.getElementById('createPatientError').style.display='none'" style="cursor:pointer;font-size:1.1rem;line-height:1;opacity:0.7;">&times;</span>
</div>

<div class="row">
    <div class="col-lg-8 offset-lg-2">
        <div class="card">
            <div class="card-header">
                Patient Information
            </div>
            <div class="card-body">
                <form id="createPatientForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">First Name *</label>
                            <input type="text" class="form-control" name="fname" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Last Name</label>
                            <input type="text" class="form-control" name="lname">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Contact Number *</label>
                            <input type="text" class="form-control" name="contact_no" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" class="form-control" name="dob">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Age</label>
                            <input type="number" class="form-control" name="age" min="0" max="150">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Gender</label>
                            <select class="form-select" name="gender">
                                <option value="">Select Gender</option>
                                <option value="M">Male</option>
                                <option value="F">Female</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Marital Status</label>
                            <select class="form-select" name="mrg_status">
                                <option value="">-- Select --</option>
                                <option value="S">Single</option>
                                <option value="M">Married</option>
                                <option value="D">Divorced</option>
                                <option value="W">Widowed</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Diet</label>
                            <select class="form-select" name="veg">
                                <option value="">-- Select --</option>
                                <option value="V">Vegetarian</option>
                                <option value="NV">Non-Vegetarian</option>
                                <option value="EV">Eggetarian</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Religion</label>
                            <input type="text" class="form-control" name="religion">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Referred By</label>
                            <input type="text" class="form-control" name="refered_by">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Occupation</label>
                            <input type="text" class="form-control" name="occupation">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Education</label>
                            <input type="text" class="form-control" name="education">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Patient ID</label>
                            <input type="text" class="form-control" name="patient_id" placeholder="Auto-generated if left empty">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Chief Complaint</label>
                        <textarea class="form-control" name="chief" rows="3" placeholder="Main reason for visit..."></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Address</label>
                            <input type="text" class="form-control" name="address">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">City</label>
                            <input type="text" class="form-control" name="city" value="Ahmedabad">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">State</label>
                            <input type="text" class="form-control" name="state" value="Gujarat">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">ZIP Code</label>
                            <input type="text" class="form-control" name="zip">
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="fas fa-save"></i> Create Patient
                            </button>
                            <a href="/patients" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Cancel
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('createPatientForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const submitBtn = document.getElementById('submitBtn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';

    const formData = new FormData(this);
    const data = new URLSearchParams(formData);

    const errorBox = document.getElementById('createPatientError');
    const errorMsg = document.getElementById('createPatientErrorMsg');
    function showError(msg) {
        errorMsg.textContent = msg;
        errorBox.style.display = 'flex';
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
    errorBox.style.display = 'none';

    try {
        const response = await fetch('/patient/create', {
            method: 'POST',
            body: data
        });

        const result = await response.json();

        if (result.success) {
            // Redirect to the patient list; the banner there confirms creation
            window.location.href = '/patients?created=1';
        } else {
            showError(result.message || 'Failed to create patient');
        }
    } catch (error) {
        console.error('Error:', error);
        showError('An error occurred while creating the patient');
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-save"></i> Create Patient';
    }
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>
