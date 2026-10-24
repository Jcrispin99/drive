<script setup lang="ts">
import { HttpResponseError } from '@inertiajs/core';
import { router, useHttp, usePage } from '@inertiajs/vue3';
import { CloudUpload, FolderUp } from 'lucide-vue-next';
import { computed, onBeforeUnmount, ref } from 'vue';
import { toast } from 'vue-sonner';
import { Button } from '@/components/ui/button';
import { store } from '@/routes/drive/upload';

const props = defineProps<{
    folderId: number | null;
}>();

const fileInput = ref<HTMLInputElement | null>(null);
const folderInput = ref<HTMLInputElement | null>(null);
const isDragging = ref(false);
const uploading = ref(false);
const currentName = ref('');
const currentIndex = ref(0);
const totalFiles = ref(0);
const page = usePage();
const maxSizeMb = computed(() => page.props.driveUpload.maxSizeMb);
const maxSizeLabel = computed(() =>
    maxSizeMb.value >= 1024
        ? `${maxSizeMb.value / 1024} GB`
        : `${maxSizeMb.value} MB`,
);
const upload = useHttp<
    {
        file: File | null;
        parent_id: number | null;
        relative_path: string | null;
    },
    { file: { id: number } }
>({ file: null, parent_id: null, relative_path: null });
const percentage = computed(() => upload.progress?.percentage ?? 0);
let disposed = false;

onBeforeUnmount(() => {
    disposed = true;
    upload.cancel();
});

async function uploadOne(file: File, relativePath: string | null) {
    if (file.size > maxSizeMb.value * 1024 * 1024) {
        toast.error(
            `${file.name}: el máximo por archivo es ${maxSizeLabel.value}.`,
        );

        return false;
    }

    upload.file = file;
    upload.parent_id = props.folderId;
    upload.relative_path = relativePath;
    upload.clearErrors();

    try {
        const response = await upload.post(store.url());

        if (!response?.file?.id) {
            toast.error(
                `${file.name}: el servidor no confirmó la carga. Recarga la página e inténtalo de nuevo.`,
            );

            return false;
        }

        return true;
    } catch (error) {
        if (disposed) {
            return false;
        }

        let message =
            'No se pudo completar la carga. Comprueba tu conexión e inténtalo de nuevo.';

        if (error instanceof HttpResponseError) {
            const status = error.response.status;

            if (status === 413) {
                message = 'El archivo supera el límite de carga del servidor.';
            } else if (status === 401 || status === 419) {
                message =
                    'Tu sesión venció. Recarga la página e inicia sesión de nuevo.';
            } else if (status === 403) {
                message =
                    'No tienes permiso para subir archivos en esta carpeta.';
            } else if (status === 422) {
                message =
                    Object.values(upload.errors).flat().join(' ') ||
                    'Revisa el archivo e inténtalo de nuevo.';
            } else {
                message =
                    'El servidor no pudo guardar el archivo. Inténtalo de nuevo.';
            }
        }

        toast.error(`${file.name}: ${message}`);

        return false;
    }
}

async function handleFiles(
    filesList: FileList | null,
    useRelativePath = false,
) {
    if (!filesList || filesList.length === 0) {
        return;
    }

    if (uploading.value) {
        toast.message(
            'Espera a que termine la carga actual para añadir más archivos.',
        );

        return;
    }

    const files = Array.from(filesList);
    totalFiles.value = files.length;
    uploading.value = true;
    let succeeded = 0;

    try {
        for (const [index, file] of files.entries()) {
            if (disposed) {
                break;
            }

            currentIndex.value = index + 1;
            currentName.value = file.name;
            const relativePath = useRelativePath
                ? file.webkitRelativePath || null
                : null;

            if (await uploadOne(file, relativePath)) {
                succeeded++;
            }
        }

        if (disposed) {
            return;
        }

        if (succeeded === files.length) {
            toast.success(
                `Se subieron ${succeeded} archivo${succeeded === 1 ? '' : 's'}.`,
            );
        } else if (succeeded > 0) {
            toast.warning(
                `Se subieron ${succeeded} de ${files.length} archivos. Reintenta los que fallaron.`,
            );
        }

        if (succeeded > 0) {
            router.reload({ only: ['folders', 'files'] });
        }
    } finally {
        uploading.value = false;
        upload
            .defaults({ file: null, parent_id: null, relative_path: null })
            .reset();
    }
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
                    Todos los formatos, incluidos PowerPoint (.ppt y .pptx).
                    Máximo {{ maxSizeLabel }} por archivo.
                </p>
            </div>
            <div
                v-if="uploading"
                class="w-full max-w-md space-y-2"
                role="status"
                aria-live="polite"
            >
                <p class="truncate text-sm">
                    {{ currentIndex }} de {{ totalFiles }}: {{ currentName }}
                </p>
                <progress
                    class="h-2 w-full accent-primary"
                    :value="percentage"
                    max="100"
                    :aria-label="`Progreso de ${currentName}`"
                />
                <p class="text-sm text-muted-foreground">
                    {{
                        percentage >= 100
                            ? 'Guardando archivo…'
                            : `Subiendo… ${percentage}%`
                    }}
                </p>
            </div>
            <div class="flex gap-2">
                <Button
                    variant="outline"
                    :disabled="uploading"
                    @click="fileInput?.click()"
                >
                    <CloudUpload class="size-4" /> Subir archivos
                </Button>
                <Button
                    variant="outline"
                    :disabled="uploading"
                    @click="folderInput?.click()"
                >
                    <FolderUp class="size-4" /> Subir carpeta
                </Button>
            </div>
            <input
                ref="fileInput"
                type="file"
                multiple
                class="hidden"
                @change="onFileChange"
            />
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
