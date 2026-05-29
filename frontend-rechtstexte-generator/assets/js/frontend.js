(function () {
	const wizard = document.querySelector('[data-frg-wizard]');
	if (!wizard) {
		return;
	}

	const steps = Array.from(wizard.querySelectorAll('.frg-step'));
	const form = wizard.querySelector('[data-frg-form]');
	const progressFill = wizard.querySelector('[data-frg-progress-fill]');
	const progressLabel = wizard.querySelector('[data-frg-progress-label]');
	const feedback = wizard.querySelector('[data-frg-feedback]');
	const previewContainer = wizard.querySelector('[data-frg-preview-container]');
	const saveStatus = wizard.querySelector('[data-frg-save-status]');
	const impressumOutput = wizard.querySelector('[data-frg-html-output="impressum"]');
	const privacyOutput = wizard.querySelector('[data-frg-html-output="privacy"]');
	let currentStep = 0;

	const setFeedback = (message, success) => {
		feedback.textContent = message || '';
		feedback.classList.toggle('is-success', !!success);
	};

	const showStep = (index) => {
		currentStep = Math.max(0, Math.min(index, steps.length - 1));
		steps.forEach((step, idx) => step.classList.toggle('is-active', idx === currentStep));
		progressFill.style.width = `${((currentStep + 1) / steps.length) * 100}%`;
		progressLabel.textContent = `Schritt ${currentStep + 1} von ${steps.length}`;
	};

	const validateStep = () => {
		const inputs = Array.from(steps[currentStep].querySelectorAll('[required]'));
		const invalid = inputs.find((input) => !input.value.trim());
		if (invalid) {
			invalid.focus();
			setFeedback(frgWizard.requiredMessage, false);
			return false;
		}
		setFeedback('', false);
		return true;
	};

	const getPayload = () => new FormData(form);

	const updateHtmlOutputs = (impressumHtml, privacyHtml) => {
		if (impressumOutput && typeof impressumHtml === 'string') {
			impressumOutput.value = impressumHtml.trim();
		}
		if (privacyOutput && typeof privacyHtml === 'string') {
			privacyOutput.value = privacyHtml.trim();
		}
	};

	const postAction = async (action, extra = {}) => {
		const body = getPayload();
		body.append('action', action);
		Object.entries(extra).forEach(([key, value]) => body.append(key, value));

		const response = await fetch(frgWizard.ajaxUrl, { method: 'POST', body, credentials: 'same-origin' });
		const text = await response.text();

		try {
			return JSON.parse(text);
		} catch (error) {
			return {
				success: false,
				data: {
					message: text && text.trim() ? text.trim() : 'Unerwartete Serverantwort.',
				},
			};
		}
	};

	wizard.querySelector('[data-frg-next]').addEventListener('click', () => {
		if (!validateStep()) {
			return;
		}
		showStep(currentStep + 1);
	});

	wizard.querySelector('[data-frg-prev]').addEventListener('click', () => {
		showStep(currentStep - 1);
	});

	wizard.querySelector('[data-frg-preview]').addEventListener('click', async () => {
		setFeedback('', false);
		const result = await postAction('frg_generate_preview');
		if (!result.success) {
			setFeedback(result.data?.message || 'Preview fehlgeschlagen.', false);
			return;
		}
		previewContainer.innerHTML = result.data.html;
		updateHtmlOutputs(result.data?.impressum_export || '', result.data?.privacy_export || '');
		setFeedback('', true);
	});

	wizard.querySelector('[data-frg-save]').addEventListener('click', async () => {
		const result = await postAction('frg_save_profile');
		if (!result.success) {
			setFeedback(result.data?.message || frgWizard.loginMessage, false);
			return;
		}
		updateHtmlOutputs(result.data?.impressum || '', result.data?.privacy || '');
		setFeedback(result.data.message || frgWizard.savedMessage, true);
		if (saveStatus && result.data?.updated_at) {
			saveStatus.textContent = `${frgWizard.savedInfoLabel}: ${result.data.updated_at}`;
		}
	});

	wizard.querySelectorAll('[data-frg-sync]').forEach((button) => {
		button.addEventListener('click', async () => {
			button.disabled = true;
			const result = await postAction('frg_sync_pages', { sync_target: button.getAttribute('data-frg-sync') });
			if (!result.success) {
				setFeedback(result.data?.message || frgWizard.syncErrorMessage || 'Sync fehlgeschlagen.', false);
				button.disabled = false;
				return;
			}
			const pageId =
				button.getAttribute('data-frg-sync') === 'impressum'
					? result.data?.result?.impressum_page_id
					: result.data?.result?.privacy_page_id;
			const message = pageId
				? `${result.data.message || frgWizard.syncMessage} (ID ${pageId})`
				: result.data.message || frgWizard.syncMessage;
			setFeedback(message, true);
			button.disabled = false;
		});
	});

	wizard.querySelectorAll('[data-frg-copy-target]').forEach((button) => {
		button.addEventListener('click', async () => {
			const target = button.getAttribute('data-frg-copy-target');
			const output = wizard.querySelector(`[data-frg-html-output="${target}"]`);
			const html = output ? output.value.trim() : '';
			if (!html) {
				setFeedback(frgWizard.generateFirstMessage, false);
				return;
			}
			await navigator.clipboard.writeText(html);
			setFeedback(
				target === 'impressum' ? frgWizard.copyImpressumMessage : frgWizard.copyPrivacyMessage,
				true
			);
		});
	});

	wizard.querySelectorAll('[data-frg-apply-scan]').forEach((button) => {
		button.addEventListener('click', () => {
			const group = button.getAttribute('data-frg-target-group');
			const key = button.getAttribute('data-frg-target-key');
			const selector = `[name="${group}[${key}]"]`;
			const target = form.querySelector(selector);
			if (!target) {
				setFeedback('Der erkannte Dienst konnte im Formular nicht zugeordnet werden.', false);
				return;
			}
			target.checked = true;
			button.disabled = true;
			setFeedback(frgWizard.adoptMessage, true);
		});
	});

	showStep(0);
})();
