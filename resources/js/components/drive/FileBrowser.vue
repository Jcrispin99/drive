<script setup lang="ts">
import { ref } from 'vue';
import FileCard from './FileCard.vue';
import FolderCard from './FolderCard.vue';
import RenameDialog from './RenameDialog.vue';
import FilePreviewModal from './FilePreviewModal.vue';
import ShareModal from './ShareModal.vue';

type FolderRow = {
    id: number;
    name: string;
    starred: boolean;
};
type FileRow = {
    id: number;
    name: string;
    extension: string | null;
    mime_type: string;
    size: number;
    starred: boolean;
};

defineProps<{
    folders: FolderRow[];
    files: FileRow[];
}>();

const renameTarget = ref<{ kind: 'folder' | 'file'; id: number; name: string } | null>(null);
const previewTarget = ref<{ id: number; name: string; mime_type: string } | null>(null);
const shareTarget = ref<{ id: number; name: string; type: 'file' | 'folder' } | null>(null);

function onFolderRename(folder: { id: number; name: string }) {
    renameTarget.value = { kind: 'folder', id: folder.id, name: folder.name };
}

function onFileRename(file: { id: number; name: string }) {
    renameTarget.value = { kind: 'file', id: file.id, name: file.name };
}
</script>

<template>
    <div v-if="folders.length === 0 && files.length === 0" class="rounded-xl border bg-muted/30 p-12 text-center">
        <p class="text-sm text-muted-foreground">Esta carpeta está vacía. Sube archivos para empezar.</p>
    </div>

    <div v-else class="space-y-6">
        <section v-if="folders.length > 0">
            <h2 class="mb-3 text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                Carpetas
            </h2>
            <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                <FolderCard
                    v-for="folder in folders"
                    :key="folder.id"
                    :folder="folder"
                    @rename="onFolderRename"
                    @share="(f) => (shareTarget = f)"
                />
            </div>
        </section>

        <section v-if="files.length > 0">
            <h2 class="mb-3 text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                Archivos
            </h2>
            <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                <FileCard
                    v-for="file in files"
                    :key="file.id"
                    :file="file"
                    @rename="onFileRename"
                    @preview="(f) => (previewTarget = f)"
                    @share="(f) => (shareTarget = f)"
                />
            </div>
        </section>
    </div>

    <RenameDialog
        v-if="renameTarget"
        :item="renameTarget"
        @close="renameTarget = null"
    />
    <FilePreviewModal
        v-if="previewTarget"
        :file="previewTarget"
        @close="previewTarget = null"
    />
    <ShareModal
        v-if="shareTarget"
        :item="shareTarget"
        @close="shareTarget = null"
    />
</template>
