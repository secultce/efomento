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
            { label: 'Nome do Agente', value: '[agent_name]' },
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

function handleImage(event, type, index) {
    const file = event.target.files?.[0];

    if (!file) return;

    const image = {
        file,
        url: URL.createObjectURL(file),
    };

    if (type === 'header') {
        headerImages.value[index] = image;
    } else {
        footerImages.value[index] = image;
    }
}

const handleTC = () => {
    const payload = new FormData();

    props.projectIds.forEach((id) => {
        payload.append('selected_projects[]', id);
    });

    payload.append('content', form.content);

    headerImages.value.forEach((img, index) => {
        if (img?.file) {
            payload.append(`header_images[${index}]`, img.file);
        }
    });

    footerImages.value.forEach((img, index) => {
        if (img?.file) {
            payload.append(`footer_images[${index}]`, img.file);
        }
    });

    saveTC(payload, {
        onSuccess: () => {
            showSnackbar('Termos criados com sucesso!', 'success');

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
        if (open) {
            if (props.editData?.content) {
                form.content = props.editData.content;
            } else {
                form.reset();

                headerImages.value = [null, null, null];
                footerImages.value = [null, null, null];
            }
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
                <p class="font-semibold mb-1">Imagens de cabeçalho</p>
                <div class="border rounded-xl p-6 mb-6">
                    <div class="grid grid-cols-3 gap-6">
                        <div v-for="(_, index) in headerImages" :key="index" class="flex flex-col items-center">
                            <label class="cursor-pointer">
                                <input
                                    hidden
                                    type="file"
                                    accept="image/*"
                                    @change="handleImage($event, 'header', index)"
                                />

                                <div
                                    class="w-[170px] h-[100px] border-2 border-dashed border-emerald-300 rounded-lg overflow-hidden flex items-center justify-center"
                                >
                                    <img
                                        v-if="headerImages[index]"
                                        :src="headerImages[index].url"
                                        class="w-full h-full object-cover"
                                    />

                                    <span v-else class="text-3xl"> + </span>
                                </div>
                            </label>

                            <span class="text-sm mt-2">
                                {{
                                    [
                                        'Adicione a imagem da esquerda',
                                        'Adicione a imagem do centro',
                                        'Adicione a imagem da direita',
                                    ][index]
                                }}
                            </span>
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
                            <label class="cursor-pointer">
                                <input
                                    hidden
                                    type="file"
                                    accept="image/*"
                                    @change="handleImage($event, 'footer', index)"
                                />

                                <div
                                    class="w-[170px] h-[100px] border-2 border-dashed border-emerald-300 rounded-lg overflow-hidden flex items-center justify-center"
                                >
                                    <img
                                        v-if="footerImages[index]"
                                        :src="footerImages[index].url"
                                        class="w-full h-full object-cover"
                                    />

                                    <span v-else class="text-3xl"> + </span>
                                </div>
                            </label>

                            <span class="text-sm mt-2">
                                {{
                                    [
                                        'Adicione a imagem da esquerda',
                                        'Adicione a imagem do centro',
                                        'Adicione a imagem da direita',
                                    ][index]
                                }}
                            </span>
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
