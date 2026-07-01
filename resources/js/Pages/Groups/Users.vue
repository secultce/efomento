<script setup>
defineProps({
    users: { type: Array, required: true },
});
</script>

<template>
    <div v-for="user in users" :key="user.id">
        <v-sheet class="pa-2" border>
            <v-row align="center" no-gutters>
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
                <v-col cols="4">
                    <h4 class="font-weight-bold">{{ user.name }}</h4>
                </v-col>
                <v-col cols="4">
                    <v-chip v-if="user.roles.length === 0" size="small" color="grey" variant="tonal" class="mr-1">
                        sem atribuição
                    </v-chip>
                    <v-chip
                        v-for="role in user.roles"
                        :key="role.name"
                        size="small"
                        color="primary"
                        variant="tonal"
                        class="mr-1"
                    >
                        {{ role.label }}
                    </v-chip>
                </v-col>
                <v-col cols="2" class="d-flex justify-end">
                    <v-btn variant="outlined" color="primary" rounded="lg" height="25">atribuir função</v-btn>
                </v-col>
            </v-row>
        </v-sheet>
    </div>
</template>
