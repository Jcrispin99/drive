<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { File as FileIcon, Folder as FolderIcon, RotateCcw, Trash2 } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import drive from '@/routes/drive';

const props = defineProps<{
    folders: Array<{ id: number; name: string; path: string; deleted_at: string | null }>;
    files: Array<{
        id: number;
        name: string;
        extension: string | null;
        mime_type: string;
        size: number;
        deleted_at: string | null;
    }>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Papelera', href: drive.trash.index().url }],
    },
});

function restore(type: 'folder' | 'file', id: number) {
    router.post(drive.trash.restore.url({ type, id }), {}, { preserveScroll: true });
}

function purge(type: 'folder' | 'file', id: number, name: string) {
    if (!confirm(`Eliminar "${name}" permanentemente?`)) return;
    router.delete(drive.trash.purge.url({ type, id }), { preserveScroll: true });
}

function emptyTrash() {
    if (!confirm('Vaciar la papelera permanentemente?')) return;
    router.delete(drive.trash.empty.url(), { preserveScroll: true });
}

const isEmpty = props.folders.length === 0 && props.files.length === 0;
</script>

<template>
    <Head title="Papelera" />

    <div class="flex flex-col gap-4 p-4">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold">Papelera</h1>
            <Button variant="destructive" :disabled="isEmpty" @click="emptyTrash">
                <Trash2 class="size-4" /> Vaciar papelera
            </Button>
        </div>

        <p v-if="isEmpty" class="text-sm text-muted-foreground">La papelera está vacía.</p>

        <ul v-else class="space-y-2">
            <li
                v-for="folder in folders"
                :key="`f-${folder.id}`"
                class="flex items-center gap-3 rounded-lg border bg-card p-3"
            >
                <FolderIcon class="size-5 text-blue-500" />
                <span class="flex-1 truncate text-sm">{{ folder.name }}</span>
                <Button variant="outline" size="sm" @click="restore('folder', folder.id)">
                    <RotateCcw class="size-4" /> Restaurar
                </Button>
                <Button variant="destructive" size="sm" @click="purge('folder', folder.id, folder.name)">
                    <Trash2 class="size-4" />
                </Button>
            </li>
            <li
                v-for="file in files"
                :key="`d-${file.id}`"
                class="flex items-center gap-3 rounded-lg border bg-card p-3"
            >
                <FileIcon class="size-5 text-muted-foreground" />
                <span class="flex-1 truncate text-sm">{{ file.name }}</span>
                <Button variant="outline" size="sm" @click="restore('file', file.id)">
                    <RotateCcw class="size-4" /> Restaurar
                </Button>
                <Button variant="destructive" size="sm" @click="purge('file', file.id, file.name)">
                    <Trash2 class="size-4" />
                </Button>
            </li>
        </ul>
    </div>
</template>
