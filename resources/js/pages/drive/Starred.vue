<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import FileBrowser from '@/components/drive/FileBrowser.vue';
import drive from '@/routes/drive';

defineProps<{
    folders: Array<{ id: number; name: string; starred: boolean }>;
    files: Array<{
        id: number;
        name: string;
        extension: string | null;
        mime_type: string;
        size: number;
        starred: boolean;
    }>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Favoritos', href: drive.starred.index().url }],
    },
});
</script>

<template>
    <Head title="Favoritos" />

    <div class="flex flex-col gap-4 p-4">
        <h1 class="text-2xl font-bold">Favoritos</h1>
        <FileBrowser :folders="folders" :files="files" />
    </div>
</template>
