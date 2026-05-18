import { ref } from 'vue';

const isOpen = ref(false);
const title = ref('');
const message = ref('');
const buttonText = ref('OK');
const onConfirm = ref(() => {});

export function useAlert() {
    const open = ({ alertTitle = 'Alert', alertMessage = '', confirmText = 'OK', action = () => {} }) => {
        title.value = alertTitle;
        message.value = alertMessage;
        buttonText.value = confirmText;
        onConfirm.value = action;

        isOpen.value = true;
    };

    const close = () => {
        isOpen.value = false;
    };

    const confirm = () => {
        onConfirm.value();
        close();
    };

    return {
        isOpen,
        title,
        message,
        buttonText,
        open,
        close,
        confirm,
    };
}
