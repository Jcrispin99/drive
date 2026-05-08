<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import FileBrowser from '@/components/drive/FileBrowser.vue';
import NewFolderDialog from '@/components/drive/NewFolderDialog.vue';
import UploadDropzone from '@/components/drive/UploadDropzone.vue';
import drive from '@/routes/drive';

defineProps<{
    folders: Array<{
        id: number;
        name: string;
        starred: boolean;
    }>;
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
        breadcrumbs: [{ title: 'Mi Drive', href: drive.index().url }],
    },
});
</script>

<template>
    <Head title="Mi Drive" />

    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold">Mi Drive</h1>
                <p class="text-sm text-muted-foreground">
                    Tus archivos y carpetas en un solo lugar.
                </p>
            </div>
            <NewFolderDialog :parent-id="null" />
        </div>

        <UploadDropzone :folder-id="null" />

        <FileBrowser :folders="folders" :files="files" />
    </div>
</template>
