<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { File as FileIcon, Folder as FolderIcon } from 'lucide-vue-next';
import drive from '@/routes/drive';

defineProps<{
    folders: Array<{
        id: number;
        name: string;
        path: string;
        shared_by: string;
        permission: 'view' | 'edit';
    }>;
    files: Array<{
        id: number;
        name: string;
        mime_type: string;
        size: number;
        shared_by: string;
        permission: 'view' | 'edit';
    }>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Compartido conmigo', href: drive.shared.index().url }],
    },
});
</script>

<template>
    <Head title="Compartido conmigo" />

    <div class="flex flex-col gap-4 p-4">
        <h1 class="text-2xl font-bold">Compartido conmigo</h1>

        <p
            v-if="folders.length === 0 && files.length === 0"
            class="text-sm text-muted-foreground"
        >Nadie ha compartido nada contigo aún.</p>

        <ul v-else class="space-y-2">
            <li
                v-for="folder in folders"
                :key="`f-${folder.id}`"
                class="flex items-center gap-3 rounded-lg border bg-card p-3"
            >
                <FolderIcon class="size-5 text-blue-500" />
                <Link
                    :href="drive.folders.show.url({ folder: folder.id })"
                    class="flex-1 truncate text-sm font-medium hover:underline"
                >{{ folder.name }}</Link>
                <span class="text-xs text-muted-foreground">de {{ folder.shared_by }}</span>
                <span class="rounded bg-muted px-2 py-0.5 text-xs">{{ folder.permission }}</span>
            </li>
            <li
                v-for="file in files"
                :key="`d-${file.id}`"
                class="flex items-center gap-3 rounded-lg border bg-card p-3"
            >
                <FileIcon class="size-5 text-muted-foreground" />
                <a
                    :href="drive.files.preview.url({ file: file.id })"
                    target="_blank"
                    rel="noopener"
                    class="flex-1 truncate text-sm font-medium hover:underline"
                >{{ file.name }}</a>
                <span class="text-xs text-muted-foreground">de {{ file.shared_by }}</span>
                <span class="rounded bg-muted px-2 py-0.5 text-xs">{{ file.permission }}</span>
            </li>
        </ul>
    </div>
</template>
