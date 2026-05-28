<script setup>
import { computed, watch, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useSnackbar } from '@/Composables/useSnackbar';
import AppTextEditor from '@/Components/AppTextEditor.vue';
import { saveTC } from '@/Services/documentService';

function insertPlaceholder(value) {
    window.tinymce?.activeEditor?.insertContent(value);
}

const { showSnackbar } = useSnackbar();

const props = defineProps({
    modelValue: Boolean,
    projectIds: { type: Array, default: () => [] },
    editData: { type: Object, default: null },
    placeholders: {
        type: Array,
        default: () => [
            { label: 'Nome do edital', value: '[notice_name]' },
            { label: 'Nup Mãe', value: '[nup_mother]' },
            { label: 'Nup Projeto', value: '[project_nup]' },
            { label: 'Nome do Agente', value: '[agent_name]' },
            { label: 'Cpf do Agente', value: '[agent_cpf]' },
            { label: 'Endereço do Agente', value: '[agent_address]' },
            { label: 'Email do Agente', value: '[agent_email]' },
            { label: 'Telefone do Agente', value: '[agent_phone]' },
            { label: 'Finalidade', value: '[finality]' },
            { label: 'Matrícula do Fiscal', value: '[fiscal_matricula]' },
            { label: 'Nome do Fiscal', value: '[fiscal_name]' },
            { label: 'Nome do projeto', value: '[project_name]' },
        ],
    },
});

const form = useForm({
    content: '',
});

const headerImages = ref([null, null, null]);
const footerImages = ref([null, null, null]);

const emit = defineEmits(['update:modelValue']);

const isOpen = computed({
    get: () => props.modelValue,
    set: (val) => emit('update:modelValue', val),
});

function normalizeImages(images = []) {
    const slots = [null, null, null];

    images.forEach((img) => {
        const index = img.position === 'left' ? 0 : img.position === 'center' ? 1 : img.position === 'right' ? 2 : null;

        if (index === null) return;

        slots[index] = {
            id: img.id,
            url: `/storage/${img.path}`,
            file: null,
            removed: false,
        };
    });

    return slots;
}

function handleImage(event, type, index) {
    const file = event.target.files?.[0];
    if (!file) return;

    const image = {
        file,
        url: URL.createObjectURL(file),
        id: null,
        removed: false,
    };

    if (type === 'header') {
        headerImages.value[index] = image;
    } else {
        footerImages.value[index] = image;
    }
}

function removeImage(type, index) {
    const target = type === 'header' ? headerImages : footerImages;

    const current = target.value[index];

    if (current?.id) {
        current.removed = true;
        current.file = null;
        current.url = null;
    } else {
        target.value[index] = null;
    }
}

function appendImages(payload, key, images) {
    images.forEach((img, index) => {
        if (!img) {
            payload.append(`${key}[${index}][_delete]`, '1');
            return;
        }

        if (img.file) {
            payload.append(`${key}[${index}][file]`, img.file);
        }

        if (img.id) {
            payload.append(`${key}[${index}][id]`, img.id);
        }

        if (img.removed) {
            payload.append(`${key}[${index}][_delete]`, '1');
        }
    });
}

const handleTC = () => {
    const payload = new FormData();

    props.projectIds.forEach((id) => {
        payload.append('selected_projects[]', id);
    });

    payload.append('content', form.content);

    appendImages(payload, 'header_images', headerImages.value);
    appendImages(payload, 'footer_images', footerImages.value);

    saveTC(payload, {
        onSuccess: () => {
            showSnackbar('Termo salvo com sucesso!', 'success');
            closeDialog();
        },
        onError: (errors) => {
            showSnackbar(Object.values(errors).flat().join(', '), 'error');
        },
    });
};

watch(
    () => isOpen.value,
    (open) => {
        if (!open) return;

        if (props.editData) {
            form.content = props.editData.content || '';

            headerImages.value = normalizeImages(props.editData.headerImages || []);
            footerImages.value = normalizeImages(props.editData.footerImages || []);
        } else {
            form.reset();
            headerImages.value = [null, null, null];
            footerImages.value = [null, null, null];
        }
    }
);

const closeDialog = () => {
    form.reset();
    form.clearErrors();

    headerImages.value = [null, null, null];
    footerImages.value = [null, null, null];

    emit('update:modelValue', false);
};
</script>

<template>
    <v-dialog v-model="isOpen" max-width="900" :retain-focus="false" persistent>
        <v-card class="rounded-lg flex flex-col" height="850">
            <v-card-title class="font-weight-bold shrink-0">
                Crie e edite um termo para enviar aos selecionados (TC)
            </v-card-title>

            <v-container class="flex flex-col overflow-y-auto">
                <!-- HEADER IMAGES -->
                <p class="font-semibold mb-1">Imagens de cabeçalho</p>
                <div class="border rounded-xl p-6 mb-6">
                    <div class="grid grid-cols-3 gap-6">
                        <div v-for="(_, index) in headerImages" :key="index" class="flex flex-col items-center">
                            <div class="relative group">
                                <label class="cursor-pointer">
                                    <input
                                        hidden
                                        type="file"
                                        accept="image/*"
                                        @click="$event.target.value = ''"
                                        @change="handleImage($event, 'header', index)"
                                    />

                                    <div
                                        class="w-[170px] h-[100px] border-2 border-dashed border-emerald-300 rounded-lg overflow-hidden flex items-center justify-center"
                                    >
                                        <img
                                            v-if="headerImages[index] && !headerImages[index].removed"
                                            :src="headerImages[index].url"
                                            class="w-full h-full object-cover"
                                        />
                                        <span v-else class="text-3xl text-emerald-600"> + </span>
                                    </div>
                                </label>

                                <button
                                    v-if="headerImages[index] && !headerImages[index].removed"
                                    class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-7 h-7 flex items-center justify-center shadow-md hover:bg-red-600 transition-colors z-10"
                                    title="Remover imagem"
                                    @click.prevent="removeImage('header', index)"
                                >
                                    <span class="text-sm font-bold">✕</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2 mb-4">
                    <v-chip
                        v-for="p in placeholders"
                        :key="p.value"
                        size="small"
                        color="primary"
                        variant="outlined"
                        @click="insertPlaceholder(p.value)"
                    >
                        {{ p.label }}
                    </v-chip>
                </div>

                <app-text-editor v-model="form.content" :error="form.errors.content" class="flex-grow" />

                <p class="font-semibold mt-6 mb-1">Imagens de rodapé</p>
                <div class="border rounded-xl p-6">
                    <div class="grid grid-cols-3 gap-6">
                        <div v-for="(_, index) in footerImages" :key="index" class="flex flex-col items-center">
                            <div class="relative group">
                                <label class="cursor-pointer">
                                    <input
                                        hidden
                                        type="file"
                                        accept="image/*"
                                        @click="$event.target.value = ''"
                                        @change="handleImage($event, 'footer', index)"
                                    />

                                    <div
                                        class="w-[170px] h-[100px] border-2 border-dashed border-emerald-300 rounded-lg overflow-hidden flex items-center justify-center"
                                    >
                                        <img
                                            v-if="footerImages[index] && !footerImages[index].removed"
                                            :src="footerImages[index].url"
                                            class="w-full h-full object-cover"
                                        />
                                        <span v-else class="text-3xl text-emerald-600"> + </span>
                                    </div>
                                </label>

                                <button
                                    v-if="footerImages[index] && !footerImages[index].removed"
                                    class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-7 h-7 flex items-center justify-center shadow-md hover:bg-red-600 transition-colors z-10"
                                    title="Remover imagem"
                                    @click.prevent="removeImage('footer', index)"
                                >
                                    <span class="text-sm font-bold">✕</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <v-card-actions class="mt-6">
                    <v-spacer />

                    <v-btn variant="outlined" color="#004c27" class="rounded-lg" @click="closeDialog"> Cancelar </v-btn>

                    <v-btn
                        class="!shadow-none !font-bold !bg-[#ffcc05] !text-[#2d353f] rounded-lg"
                        :loading="form.processing"
                        :disabled="!form.content.trim()"
                        @click="handleTC"
                    >
                        Salvar
                    </v-btn>
                </v-card-actions>
            </v-container>
        </v-card>
    </v-dialog>
</template>
