<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ChevronRight } from 'lucide-vue-next';
import FileBrowser from '@/components/drive/FileBrowser.vue';
import NewFolderDialog from '@/components/drive/NewFolderDialog.vue';
import UploadDropzone from '@/components/drive/UploadDropzone.vue';
import drive from '@/routes/drive';

const props = defineProps<{
    folder: {
        id: number;
        name: string;
        path: string;
        parent_id: number | null;
        starred: boolean;
    };
    breadcrumbs: Array<{ id: number; name: string }>;
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
    <Head :title="folder.name" />

    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4">
        <div class="flex items-center justify-between">
            <div class="min-w-0">
                <nav class="mb-1 flex items-center gap-1 text-sm text-muted-foreground">
                    <Link :href="drive.index().url" class="hover:text-foreground">Mi Drive</Link>
                    <template v-for="(crumb, idx) in breadcrumbs" :key="crumb.id">
                        <ChevronRight class="size-4" />
                        <Link
                            v-if="idx < breadcrumbs.length - 1"
                            :href="drive.folders.show.url({ folder: crumb.id })"
                            class="hover:text-foreground"
                        >{{ crumb.name }}</Link>
                        <span v-else class="font-medium text-foreground">{{ crumb.name }}</span>
                    </template>
                </nav>
                <h1 class="truncate text-2xl font-bold">{{ folder.name }}</h1>
            </div>
            <NewFolderDialog :parent-id="folder.id" />
        </div>

        <UploadDropzone :folder-id="folder.id" />

        <FileBrowser :folders="folders" :files="files" />
    </div>
</template>
