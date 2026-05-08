<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Download, X } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogTitle } from '@/components/ui/dialog';
import drive from '@/routes/drive';

const props = defineProps<{
    file: { id: number; name: string; mime_type: string };
}>();

const emit = defineEmits<{ close: [] }>();

const open = ref(true);

watch(open, (val) => {
    if (!val) emit('close');
});

const isImage = computed(() => props.file.mime_type.startsWith('image/'));
const isPdf = computed(() => props.file.mime_type === 'application/pdf');

const previewUrl = computed(() => drive.files.preview.url({ file: props.file.id }));
const downloadUrl = computed(() => drive.files.download.url({ file: props.file.id }));
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="max-w-4xl">
            <div class="flex items-center justify-between">
                <DialogTitle class="truncate">{{ file.name }}</DialogTitle>
                <div class="flex gap-2">
                    <Button variant="outline" size="sm" as-child>
                        <a :href="downloadUrl">
                            <Download class="size-4" /> Descargar
                        </a>
                    </Button>
                    <Button variant="ghost" size="icon-sm" @click="open = false">
                        <X class="size-4" />
                    </Button>
                </div>
            </div>

            <div class="mt-4 max-h-[70vh] overflow-auto rounded-lg border bg-muted/20">
                <img
                    v-if="isImage"
                    :src="previewUrl"
                    :alt="file.name"
                    class="mx-auto max-h-[70vh] w-auto"
                />
                <iframe
                    v-else-if="isPdf"
                    :src="previewUrl"
                    class="h-[70vh] w-full"
                    :title="file.name"
                />
                <div v-else class="p-12 text-center text-sm text-muted-foreground">
                    No hay vista previa para este tipo de archivo.
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
