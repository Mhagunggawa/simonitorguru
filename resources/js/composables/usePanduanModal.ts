import { ref } from 'vue';

const isPanduanOpen = ref(false);

export function usePanduanModal() {
    const openPanduan = () => {
        isPanduanOpen.value = true;
    };

    const closePanduan = () => {
        isPanduanOpen.value = false;
    };

    const togglePanduan = () => {
        isPanduanOpen.value = !isPanduanOpen.value;
    };

    return {
        isPanduanOpen,
        openPanduan,
        closePanduan,
        togglePanduan,
    };
}

