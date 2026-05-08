<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { CloudUpload, FolderUp } from 'lucide-vue-next';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import { Button } from '@/components/ui/button';
import drive from '@/routes/drive';

const props = defineProps<{
    folderId: number | null;
}>();

const fileInput = ref<HTMLInputElement | null>(null);
const folderInput = ref<HTMLInputElement | null>(null);
const isDragging = ref(false);
const inFlight = ref(0);

async function uploadOne(file: File, relativePath: string | null) {
    const fd = new FormData();
    fd.append('file', file);
    if (props.folderId !== null) {
        fd.append('parent_id', String(props.folderId));
    }
    if (relativePath) {
        fd.append('relative_path', relativePath);
    }

    inFlight.value += 1;
    try {
        const res = await fetch(drive.upload.store.url(), {
            method: 'POST',
            body: fd,
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': decodeURIComponent(getCookie('XSRF-TOKEN') ?? ''),
            },
        });
        if (!res.ok) {
            throw new Error(`HTTP ${res.status}`);
        }
    } catch (e) {
        toast.error(`Error al subir ${file.name}`);
    } finally {
        inFlight.value -= 1;
    }
}

function getCookie(name: string): string | null {
    const match = document.cookie.match(new RegExp('(^|; )' + name + '=([^;]*)'));
    return match ? match[2] : null;
}

async function handleFiles(filesList: FileList | null, useRelativePath = false) {
    if (!filesList || filesList.length === 0) return;
    const total = filesList.length;
    const tasks: Promise<void>[] = [];
    for (let i = 0; i < total; i++) {
        const f = filesList.item(i);
        if (!f) continue;
        const rel = useRelativePath
            ? (f as File & { webkitRelativePath?: string }).webkitRelativePath ?? null
            : null;
        tasks.push(uploadOne(f, rel));
    }
    toast.message(`Subiendo ${total} archivo${total === 1 ? '' : 's'}…`);
    await Promise.all(tasks);
    toast.success('Subida completa');
    router.reload({ only: ['folders', 'files'] });
}

function onDrop(e: DragEvent) {
    e.preventDefault();
    isDragging.value = false;
    handleFiles(e.dataTransfer?.files ?? null, false);
}

function onFileChange(e: Event) {
    const target = e.target as HTMLInputElement;
    handleFiles(target.files, false);
    target.value = '';
}

function onFolderChange(e: Event) {
    const target = e.target as HTMLInputElement;
    handleFiles(target.files, true);
    target.value = '';
}
</script>

<template>
    <div
        class="rounded-xl border-2 border-dashed p-6 transition-colors"
        :class="isDragging ? 'border-primary bg-primary/5' : 'border-border'"
        @dragover.prevent="isDragging = true"
        @dragleave.prevent="isDragging = false"
        @drop="onDrop"
    >
        <div class="flex flex-col items-center gap-3 text-center">
            <CloudUpload class="size-8 text-muted-foreground" />
            <div>
                <p class="font-medium">Arrastra y suelta archivos o carpetas</p>
                <p class="text-sm text-muted-foreground">
                    {{ inFlight > 0 ? `Subiendo ${inFlight} archivo(s)…` : 'O usa los botones de abajo' }}
                </p>
            </div>
            <div class="flex gap-2">
                <Button variant="outline" @click="fileInput?.click()">
                    <CloudUpload class="size-4" /> Subir archivos
                </Button>
                <Button variant="outline" @click="folderInput?.click()">
                    <FolderUp class="size-4" /> Subir carpeta
                </Button>
            </div>
            <input ref="fileInput" type="file" multiple class="hidden" @change="onFileChange" />
            <input
                ref="folderInput"
                type="file"
                multiple
                webkitdirectory
                directory
                class="hidden"
                @change="onFolderChange"
            />
        </div>
    </div>
</template>
