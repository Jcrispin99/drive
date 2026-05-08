<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import FileBrowser from '@/components/drive/FileBrowser.vue';
import drive from '@/routes/drive';

defineProps<{
    files: Array<{
        id: number;
        name: string;
        extension: string | null;
        mime_type: string;
        size: number;
        starred: boolean;
        last_accessed_at: string | null;
    }>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Recientes', href: drive.recent.index().url }],
    },
});
</script>

<template>
    <Head title="Recientes" />

    <div class="flex flex-col gap-4 p-4">
        <h1 class="text-2xl font-bold">Recientes</h1>
        <p
            v-if="files.length === 0"
            class="rounded-lg border bg-muted/30 p-6 text-sm text-muted-foreground"
        >
            Todavía no tienes archivos en tu Drive.
        </p>
        <FileBrowser v-else :folders="[]" :files="files" />
    </div>
</template>
