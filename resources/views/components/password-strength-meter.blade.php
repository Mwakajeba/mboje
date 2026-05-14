@props(['inputId' => 'password', 'showMeter' => true])

@php
    $showMeter = \App\Services\SystemSettingService::get('password_require_strength_meter', true);
@endphp

@if($showMeter)
<div id="password-strength-meter-{{ $inputId }}" class="password-strength-meter mt-2" style="display: none;">
    <div class="strength-bar-container mb-2">
        <div class="progress" style="height: 5px;">
            <div id="strength-bar-{{ $inputId }}" class="progress-bar" role="progressbar" style="width: 0%; transition: width 0.3s ease, background-color 0.3s ease;"></div>
        </div>
    </div>
    <div class="strength-info">
        <small id="strength-text-{{ $inputId }}" class="text-muted"></small>
        <ul id="strength-feedback-{{ $inputId }}" class="list-unstyled mt-1 mb-0" style="font-size: 0.85rem;"></ul>
    </div>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
(function() {
    const inputId = '{{ $inputId }}';
    const passwordInput = document.getElementById(inputId);
    const meterContainer = document.getElementById('password-strength-meter-' + inputId);
    const strengthBar = document.getElementById('strength-bar-' + inputId);
    const strengthText = document.getElementById('strength-text-' + inputId);
    const strengthFeedback = document.getElementById('strength-feedback-' + inputId);

    if (!passwordInput || !meterContainer) return;

    // Strength level colors and labels
    const strengthLevels = {
        'very-weak': { color: '#dc3545', label: 'Very Weak', width: 20 },
        'weak': { color: '#fd7e14', label: 'Weak', width: 40 },
        'fair': { color: '#ffc107', label: 'Fair', width: 60 },
        'good': { color: '#0dcaf0', label: 'Good', width: 80 },
        'strong': { color: '#198754', label: 'Strong', width: 100 }
    };

    function updateStrengthMeter(password) {
        if (!password || password.length === 0) {
            meterContainer.style.display = 'none';
            return;
        }

        meterContainer.style.display = 'block';

        // Call backend API to calculate password strength
        fetch('/api/password-strength', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify({ password: password })
        })
        .then(response => response.json())
        .then(data => {
            const level = data.level || 'very-weak';
            const score = data.score || 0;
            const feedback = data.feedback || [];

            // Update progress bar
            const levelInfo = strengthLevels[level] || strengthLevels['very-weak'];
            strengthBar.style.width = levelInfo.width + '%';
            strengthBar.style.backgroundColor = levelInfo.color;
            strengthBar.setAttribute('aria-valuenow', score);
            strengthBar.setAttribute('aria-valuemin', 0);
            strengthBar.setAttribute('aria-valuemax', 100);

            // Update text
            strengthText.textContent = `Strength: ${levelInfo.label} (${score}%)`;
            strengthText.className = 'text-muted';

            // Update feedback
            strengthFeedback.innerHTML = '';
            if (feedback.length > 0) {
                feedback.forEach(item => {
                    const li = document.createElement('li');
                    li.className = 'text-warning';
                    li.textContent = '• ' + item;
                    strengthFeedback.appendChild(li);
                });
            } else if (level === 'strong') {
                const li = document.createElement('li');
                li.className = 'text-success';
                li.textContent = '✓ Password strength is good';
                strengthFeedback.appendChild(li);
            }
        })
        .catch(error => {
            console.error('Error calculating password strength:', error);
            // Fallback: simple client-side calculation
            calculateStrengthClientSide(password);
        });
    }

    function calculateStrengthClientSide(password) {
        let score = 0;
        const feedback = [];

        // Length
        if (password.length >= 12) {
            score += 25;
        } else if (password.length >= 8) {
            score += 15;
        } else if (password.length >= 6) {
            score += 5;
            feedback.push('Use at least 8 characters for better security');
        } else {
            feedback.push('Password is too short');
        }

        // Character variety
        const hasLower = /[a-z]/.test(password);
        const hasUpper = /[A-Z]/.test(password);
        const hasNumber = /[0-9]/.test(password);
        const hasSpecial = /[^A-Za-z0-9]/.test(password);

        const variety = (hasLower ? 1 : 0) + (hasUpper ? 1 : 0) + (hasNumber ? 1 : 0) + (hasSpecial ? 1 : 0);
        score += variety * 12.5;

        if (!hasLower) feedback.push('Add lowercase letters');
        if (!hasUpper) feedback.push('Add uppercase letters');
        if (!hasNumber) feedback.push('Add numbers');
        if (!hasSpecial) feedback.push('Add special characters');

        // Determine level
        let level = 'very-weak';
        if (score >= 80) level = 'strong';
        else if (score >= 60) level = 'good';
        else if (score >= 40) level = 'fair';
        else if (score >= 20) level = 'weak';

        const levelInfo = strengthLevels[level];
        strengthBar.style.width = levelInfo.width + '%';
        strengthBar.style.backgroundColor = levelInfo.color;
        strengthText.textContent = `Strength: ${levelInfo.label} (${score}%)`;

        strengthFeedback.innerHTML = '';
        feedback.forEach(item => {
            const li = document.createElement('li');
            li.className = 'text-warning';
            li.textContent = '• ' + item;
            strengthFeedback.appendChild(li);
        });
    }

    // Attach event listener
    passwordInput.addEventListener('input', function() {
        updateStrengthMeter(this.value);
    });

    // Initial check if password field has value
    if (passwordInput.value) {
        updateStrengthMeter(passwordInput.value);
    }
})();
</script>
@endif

