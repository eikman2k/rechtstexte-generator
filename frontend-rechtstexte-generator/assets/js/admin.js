(function () {
	const root = document.querySelector('.frg-admin');
	if (!root || typeof frgAdmin === 'undefined') {
		return;
	}

	const postAction = async (action, blockKey, extra = {}) => {
		const body = new FormData();
		body.append('action', action);
		body.append('nonce', frgAdmin.nonce);
		body.append('block_key', blockKey);
		Object.entries(extra).forEach(([key, value]) => {
			body.append(key, value);
		});

		const response = await fetch(frgAdmin.ajaxUrl || window.ajaxurl, {
			method: 'POST',
			body,
			credentials: 'same-origin',
		});

		return response.json();
	};

	const setInlineFeedback = (card, message, success) => {
		const target = card.querySelector('[data-frg-inline-feedback]');
		if (!target) {
			return;
		}
		target.textContent = message || '';
		target.classList.toggle('is-success', !!success);
		target.classList.toggle('is-error', !success && !!message);
	};

	root.querySelectorAll('[data-frg-generate-draft]').forEach((button) => {
		button.addEventListener('click', async () => {
			const blockKey = button.getAttribute('data-frg-generate-draft');
			const card = button.closest('[data-frg-block]');
			if (!blockKey || !card) {
				return;
			}

			setInlineFeedback(card, frgAdmin.generatingMessage, true);
			button.disabled = true;

			try {
				const result = await postAction('frg_admin_generate_block_draft', blockKey);
				if (!result.success) {
					setInlineFeedback(card, result.data?.message || frgAdmin.generateError, false);
					return;
				}

				const draftInput = card.querySelector('[data-frg-draft-input]');
				const draftPreview = card.querySelector('[data-frg-draft-preview]');
				const adoptButton = card.querySelector('[data-frg-adopt-draft]');
				const status = card.querySelector('[data-frg-block-status]');
				const statusSelect = card.querySelector('select[name$="[status]"]');

				if (draftInput) {
					draftInput.value = result.data?.draft_text || '';
				}
				if (draftPreview) {
					draftPreview.innerHTML = result.data?.draft_html || '';
				}
				if (adoptButton) {
					adoptButton.disabled = !(result.data?.draft_text || '').trim();
				}
				if (status) {
					status.textContent = result.data?.status || 'draft';
				}
				if (statusSelect) {
					statusSelect.value = result.data?.status || 'draft';
				}
				setInlineFeedback(card, result.data?.message || frgAdmin.draftUpdated, true);
			} catch (error) {
				setInlineFeedback(card, frgAdmin.generateError, false);
			} finally {
				button.disabled = false;
			}
		});
	});

	root.querySelectorAll('[data-frg-adopt-draft]').forEach((button) => {
		button.addEventListener('click', async () => {
			const blockKey = button.getAttribute('data-frg-adopt-draft');
			const card = button.closest('[data-frg-block]');
			if (!blockKey || !card) {
				return;
			}

			setInlineFeedback(card, frgAdmin.adoptingMessage, true);
			button.disabled = true;

			try {
				const draftInput = card.querySelector('[data-frg-draft-input]');
				const currentDraft = draftInput ? draftInput.value : '';
				const result = await postAction('frg_admin_adopt_block_draft', blockKey, {
					draft_text: currentDraft,
				});
				if (!result.success) {
					setInlineFeedback(card, result.data?.message || frgAdmin.adoptError, false);
					return;
				}

				const overridePreview = card.querySelector('[data-frg-override-preview]');
				const overrideInput = card.querySelector('[data-frg-override-input]');
				const activePreview = card.querySelector('[data-frg-active-preview]');
				const status = card.querySelector('[data-frg-block-status]');
				const statusSelect = card.querySelector('select[name$="[status]"]');
				const lastReviewed = card.querySelector('[data-frg-last-reviewed]');
				const draftPreview = card.querySelector('[data-frg-draft-preview]');
				const liveState = card.querySelector('[data-frg-live-state]');

				if (overridePreview) {
					overridePreview.innerHTML = result.data?.override_html || '';
				}
				if (overrideInput) {
					overrideInput.value = result.data?.override_text || '';
				}
				if (activePreview) {
					activePreview.innerHTML = result.data?.override_html || '';
				}
				if (draftPreview) {
					draftPreview.innerHTML = result.data?.draft_html || '';
				}
				if (status) {
					status.textContent = result.data?.status || 'approved';
				}
				if (statusSelect) {
					statusSelect.value = result.data?.status || 'approved';
				}
				if (lastReviewed && result.data?.last_reviewed) {
					lastReviewed.value = result.data.last_reviewed;
				}
				if (liveState) {
					liveState.textContent = 'Status: Live-Override gespeichert.';
				}
				setInlineFeedback(card, result.data?.message || frgAdmin.overrideUpdated, true);
			} catch (error) {
				setInlineFeedback(card, frgAdmin.adoptError, false);
			} finally {
				button.disabled = false;
			}
		});
	});

	root.querySelectorAll('[data-frg-copy-admin]').forEach((button) => {
		button.addEventListener('click', async () => {
			const target = button.getAttribute('data-frg-copy-admin');
			const output = root.querySelector(`[data-frg-admin-html="${target}"]`);
			const feedback = root.querySelector('[data-frg-admin-copy-feedback]');
			const html = output ? output.value.trim() : '';

			if (!html) {
				if (feedback) {
					feedback.textContent = frgAdmin.copyMissingMessage;
					feedback.classList.add('is-error');
					feedback.classList.remove('is-success');
				}
				return;
			}

			try {
				await navigator.clipboard.writeText(html);
				if (feedback) {
					feedback.textContent =
						target === 'impressum' ? frgAdmin.copyImpressumMessage : frgAdmin.copyPrivacyMessage;
					feedback.classList.add('is-success');
					feedback.classList.remove('is-error');
				}
			} catch (error) {
				if (feedback) {
					feedback.textContent = frgAdmin.copyMissingMessage;
					feedback.classList.add('is-error');
					feedback.classList.remove('is-success');
				}
			}
		});
	});
})();
