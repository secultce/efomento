<script setup>
import dayjs from 'dayjs'
import relativeTime from 'dayjs/plugin/relativeTime'
import 'dayjs/locale/pt-br'

dayjs.extend(relativeTime)
dayjs.locale('pt-br')

defineProps({
    audits: { type: Array, required: true }
})

function formatRelativeDate(date) {
    return dayjs(date).fromNow()
}
</script>

<template>
    <div class="flex flex-col">

        <div v-for="audit in audits" :key="audit.id" class="py-4 px-2 border-b border-gray-200">
            <div class="flex items-start gap-3">

                <!-- Avatar -->
                <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center">
                    <v-icon size="16">mdi-account</v-icon>
                </div>

                <!-- Conteúdo -->
                <div class="flex-1">

                    <!-- Linha principal -->
                    <div class="text-sm mb-1">
                        <strong>{{ audit.user }}</strong>
                        {{ audit.action }}
                        os valores dos seguintes campos no edital:
                    </div>

                    <!-- LISTA DE ALTERAÇÕES -->
                    <ul v-if="audit.changes.length" class="text-xs text-gray-700 space-y-1 ml-6">
                        <li v-for="(change, i) in audit.changes" :key="i">
                            <strong>{{ change.label }}:</strong>

                            <span class="ml-1">
                                {{ change.from }}
                            </span>

                            <span class="mx-1 align-text-top">→</span>

                            <span class="font-medium">
                                {{ change.to }}
                            </span>
                        </li>
                    </ul>

                </div>

                <!-- Data -->
                <div class="text-xs text-gray-500 whitespace-nowrap">
                    {{ formatRelativeDate(audit.date) }}
                </div>

            </div>
        </div>

    </div>
</template>
