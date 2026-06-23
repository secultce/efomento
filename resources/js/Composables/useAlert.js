import { ref } from 'vue';

const isOpen = ref(false);
const title = ref('');
const message = ref('');
const buttonText = ref('OK');
const cancelText = ref('');
const onConfirm = ref(() => {});
const onCancel = ref(null);

export function useAlert() {
    const showAlert = ({
        alertTitle = 'Alert',
        alertMessage = '',
        confirmText = 'OK',
        action = () => {},
        cancelButtonText = '',
        cancelAction = null,
    }) => {
        title.value = alertTitle;
        message.value = alertMessage;
        buttonText.value = confirmText;
        onConfirm.value = action;
        cancelText.value = cancelButtonText;
        onCancel.value = cancelAction;

        isOpen.value = true;
    };

    const closeAlert = () => {
        isOpen.value = false;
    };

    const confirm = () => {
        onConfirm.value();
        closeAlert();
    };

    const cancel = () => {
        onCancel.value?.();
        closeAlert();
    };

    return {
        isOpen,
        title,
        message,
        buttonText,
        cancelText,
        showAlert,
        closeAlert,
        confirm,
        cancel,
    };
}
