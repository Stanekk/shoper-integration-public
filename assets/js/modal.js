const CLASSES = {
    active: 'modal--active',
    shadowActive: 'modal__shadow--active'
};

const initializedModals = new Set();

function attachCloseEvent(modalId) {
    if (initializedModals.has(modalId)) return;
    initializedModals.add(modalId);

    const modal = getModalEL(modalId);
    const shadow = getModalShadowEl(modalId);

    if (modal) {
        const close = modal.querySelector('.modal__icon-close');
        if (close) {
            close.addEventListener('click', () => {
                toggleModal(modalId);
            });
        }
    }

    if (shadow) {
        shadow.addEventListener('click', () => {
            toggleModal(modalId);
        });
    }
}

export function getModalEL(modalId) {
    return document.getElementById(modalId);
}

function getModalShadowEl(modalId) {
    const shadowElId = `modal-shadow-${modalId}`;
    return document.getElementById(shadowElId);
}

function toggleModalState(modal, shadow) {
    modal.classList.toggle(CLASSES.active);
    shadow.classList.toggle(CLASSES.shadowActive);
}

export function toggleModal(modalId) {
    if (!modalId) return;

    attachCloseEvent(modalId);

    const modal = getModalEL(modalId);
    const shadow = getModalShadowEl(modalId);

    if (!modal || !shadow) {
        console.warn(`Modal or shadow element not found for modalId: ${modalId}`);
        return;
    }

    toggleModalState(modal, shadow);
}
