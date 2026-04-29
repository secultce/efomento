<script setup>
import { useExternalLink } from '@/Composables/useExternalLink'
const { openExternal } = useExternalLink()
</script>
<template>
    <div class="w-full flex items-center gap-4">
        <div class="flex items-center gap-2 hover:border-gray-600  cursor-pointer hover:text-green-600">
           <img src="/images/logos/mini-mapa-cultural.png" alt="logo mapas" class="w-6 h-6" />
           <div>
             <p class="text-sm font-bold" @click="openExternal('https://mapacultural.secult.ce.gov.br/')">Conferir ficha de inscrição</p>
             <p class="text-xs text-gray-600">Acesse a ficha de inscrição do projeto no Mapa Cultural</p>
           </div>
        </div>
         <div class="flex items-center hover:border-gray-600 cursor-pointer hover:text-green-600">
           <img src="/images/logos/suite.png" alt="logo mapas" class="w-20 h-6" />
           <div>
             <p class="text-sm font-bold" @click="openExternal('https://suite.ce.gov.br/')">Suite</p>
             <p class="text-xs text-gray-600">Acesse o processo no suite</p>
           </div>
        </div>
         <div class="flex items-center gap-2 hover:border-gray-600 cursor-pointer hover:text-green-600">
           <img src="/images/logos/eparc.png" alt="logo mapas" class="w-20 h-6" />
           <div>
             <p class="text-sm font-bold" @click="openExternal('https://e-parcerias.cge.ce.gov.br/e-parcerias-web/padrao-web/paginas/seguranca/login.seam')">E-Parcerias</p>
             <p class="text-xs text-gray-600">Acesse o processo no E-Parcerias</p>
           </div>
        </div>
    </div>
</template>

