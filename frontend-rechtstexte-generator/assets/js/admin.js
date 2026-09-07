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

	const renderStatus = (card, statusKey) => {
		const badge = card.querySelector('[data-frg-block-status]');
		const statusSelect = card.querySelector('[data-frg-status-select]');
		if (badge) {
			badge.textContent = frgAdmin.statusLabels?.[statusKey] || statusKey;
			badge.className = `frg-badge frg-badge--${statusKey}`;
		}
		if (statusSelect) {
			statusSelect.value = statusKey;
		}
		card.dataset.frgStatus = statusKey;
	};

	const syncReviewRequirements = (card) => {
		const isLegallyReviewed = card.querySelector('[data-frg-status-select]')?.value === 'legal_reviewed';
		const reviewedAt = card.querySelector('[data-frg-last-reviewed]');
		const reviewedBy = card.querySelector('[data-frg-reviewed-by]');
		if (reviewedAt) {
			reviewedAt.required = isLegallyReviewed;
		}
		if (reviewedBy) {
			reviewedBy.required = isLegallyReviewed;
		}
	};

	const blockCards = Array.from(root.querySelectorAll('[data-frg-block]'));
	const openBlocksStorageKey = 'frg-admin-open-blocks';
	const searchInput = root.querySelector('[data-frg-block-search]');
	const areaFilter = root.querySelector('[data-frg-block-area]');
	const statusFilter = root.querySelector('[data-frg-block-status-filter]');
	const filterCount = root.querySelector('[data-frg-filter-count]');
	const feedMode = root.querySelector('[data-frg-feed-mode]');

	const updateFeedPanels = () => {
		const mode = feedMode?.value || 'off';
		root.querySelectorAll('[data-frg-feed-panel]').forEach((panel) => {
			panel.hidden = panel.dataset.frgFeedPanel !== mode;
		});
		root.querySelectorAll('[data-frg-feed-shared]').forEach((panel) => {
			panel.hidden = mode === 'off';
		});
		const syncButton = root.querySelector('[data-frg-client-sync]');
		if (syncButton) {
			syncButton.hidden = mode !== 'client';
		}
	};
	feedMode?.addEventListener('change', updateFeedPanels);
	updateFeedPanels();

	const filterBlocks = () => {
		const query = (searchInput?.value || '').trim().toLocaleLowerCase('de');
		const area = areaFilter?.value || '';
		const status = statusFilter?.value || '';
		let visible = 0;

		blockCards.forEach((card) => {
			const matchesStatus = !status ||
				(status === 'overdue' ? card.dataset.frgOverdue === '1' : card.dataset.frgStatus === status);
			const matches =
				(!query || (card.dataset.frgTitle || '').toLocaleLowerCase('de').includes(query)) &&
				(!area || card.dataset.frgArea === area) &&
				matchesStatus;
			card.hidden = !matches;
			if (matches) {
				visible += 1;
			}
		});

		if (filterCount) {
			filterCount.textContent = (frgAdmin.filterCount || '%d Bausteine').replace('%d', String(visible));
		}
	};

	[searchInput, areaFilter, statusFilter].forEach((control) => {
		control?.addEventListener(control === searchInput ? 'input' : 'change', filterBlocks);
	});
	root.querySelector('[data-frg-expand-visible]')?.addEventListener('click', () => {
		blockCards.forEach((card) => {
			if (!card.hidden) {
				card.open = true;
			}
		});
	});
	root.querySelectorAll('[data-frg-status-jump]').forEach((cardLink) => {
		cardLink.addEventListener('click', () => {
			if (statusFilter) {
				statusFilter.value = cardLink.dataset.frgStatusJump || '';
			}
			if (searchInput) {
				searchInput.value = '';
			}
			if (areaFilter) {
				areaFilter.value = '';
			}
			filterBlocks();
		});
	});
	let savedOpenBlocks = [];
	try {
		savedOpenBlocks = JSON.parse(window.sessionStorage.getItem(openBlocksStorageKey) || '[]');
	} catch (error) {
		savedOpenBlocks = [];
	}
	blockCards.forEach((card) => {
		if (savedOpenBlocks.includes(card.dataset.frgBlock)) {
			card.open = true;
		}
		card.addEventListener('toggle', () => {
			try {
				const openBlockKeys = blockCards.filter((item) => item.open).map((item) => item.dataset.frgBlock);
				window.sessionStorage.setItem(openBlocksStorageKey, JSON.stringify(openBlockKeys));
			} catch (error) {
				// The workflow remains usable when browser storage is unavailable.
			}
		});
		card.querySelector('[data-frg-status-select]')?.addEventListener('change', (event) => {
			renderStatus(card, event.target.value);
			syncReviewRequirements(card);
			filterBlocks();
		});
		syncReviewRequirements(card);
	});
	filterBlocks();

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
				const changeRequest = card.querySelector('[data-frg-change-request]')?.value || '';
				const legalBasis = card.querySelector('[data-frg-legal-basis]')?.value || '';
				const result = await postAction('frg_admin_generate_block_draft', blockKey, {
					change_request: changeRequest,
					legal_basis: legalBasis,
				});
				if (!result.success) {
					setInlineFeedback(card, result.data?.message || frgAdmin.generateError, false);
					return;
				}

				const draftInput = card.querySelector('[data-frg-draft-input]');
				const draftPreview = card.querySelector('[data-frg-draft-preview]');
				const adoptButton = card.querySelector('[data-frg-adopt-draft]');

				if (draftInput) {
					draftInput.value = result.data?.draft_text || '';
				}
				if (draftPreview) {
					draftPreview.innerHTML = result.data?.draft_html || '';
				}
				if (adoptButton) {
					adoptButton.disabled = !(result.data?.draft_text || '').trim();
				}
				renderStatus(card, result.data?.status || 'draft');
				filterBlocks();
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
				renderStatus(card, result.data?.status || 'editorial_approved');
				if (lastReviewed && result.data?.last_reviewed) {
					lastReviewed.value = result.data.last_reviewed;
				}
				if (liveState) {
					liveState.textContent = frgAdmin.publishedMessage;
				}
				filterBlocks();
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

	root.querySelectorAll('[data-frg-copy-value]').forEach((button) => {
		button.addEventListener('click', async () => {
			const input = root.querySelector(button.dataset.frgCopyValue || '');
			const feedback = root.querySelector('[data-frg-copy-value-feedback]');
			if (!input?.value) {
				if (feedback) {
					feedback.textContent = frgAdmin.copyValueMissingMessage;
				}
				return;
			}
			try {
				await navigator.clipboard.writeText(input.value);
				if (feedback) {
					feedback.textContent = frgAdmin.valueCopiedMessage;
					feedback.classList.add('is-success');
				}
			} catch (error) {
				if (feedback) {
					feedback.textContent = frgAdmin.copyValueMissingMessage;
					feedback.classList.add('is-error');
				}
			}
		});
	});
})();
