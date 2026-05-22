<script setup>
defineProps({
    users: { type: Array, required: true },
});
</script>

<template>
    <div v-for="user in users" :key="user.id">
        <v-sheet class="pa-2" border height="40">
            <v-row>
                <v-col cols="1">
                    <v-checkbox-btn></v-checkbox-btn>
                </v-col>
                <v-col cols="1">
                    <v-avatar size="25">
                        <v-img
                            alt="Avatar"
                            :src="`https://avatars0.githubusercontent.com/u/${user.id}?v=4&s=460`"
                        ></v-img>
                    </v-avatar>
                </v-col>
                <v-col cols="8">
                    <h4 class="font-weight-bold">{{ user.name }}</h4>
                </v-col>
                <v-col cols="2">
                    <v-btn variant="outlined" color="primary" rounded="lg" height="25">atribuir função</v-btn>
                </v-col>
            </v-row>
        </v-sheet>
    </div>
</template>

<style scoped></style>
