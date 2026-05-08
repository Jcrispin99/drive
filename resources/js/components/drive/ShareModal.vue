<script setup lang="ts">
import { Copy, Globe, Link as LinkIcon, Loader2, Trash2, User as UserIcon } from 'lucide-vue-next';
import { onMounted, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import drive from '@/routes/drive';

type ShareRow = {
    id: number;
    permission: 'view' | 'edit';
    shared_with: { id: number; name: string; email: string } | null;
    token: string | null;
    public_url: string | null;
    is_public_link: boolean;
    expires_at: string | null;
};

const props = defineProps<{
    item: { id: number; name: string; type: 'file' | 'folder' };
}>();

const emit = defineEmits<{ close: [] }>();

const open = ref(true);
const loadingList = ref(true);
const submittingUser = ref(false);
const submittingPublic = ref(false);
const shares = ref<ShareRow[]>([]);

const email = ref('');
const permission = ref<'view' | 'edit'>('view');
const emailError = ref<string | null>(null);

watch(open, (val) => {
    if (!val) emit('close');
});

const csrfToken = () =>
    decodeURIComponent(
        document.cookie.match(/(^|; )XSRF-TOKEN=([^;]*)/)?.[2] ?? '',
    );

const jsonHeaders = (): Record<string, string> => ({
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
    'X-XSRF-TOKEN': csrfToken(),
});

async function refreshShares() {
    loadingList.value = true;
    try {
        const res = await fetch(
            drive.shares.forItem.url({ type: props.item.type, id: props.item.id }),
            { credentials: 'same-origin', headers: jsonHeaders() },
        );
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const data = await res.json();
        shares.value = data.shares;
    } catch {
        toast.error('No se pudieron cargar los accesos');
    } finally {
        loadingList.value = false;
    }
}

onMounted(refreshShares);

async function shareWithUser() {
    if (!email.value.trim()) return;
    emailError.value = null;
    submittingUser.value = true;

    const fd = new FormData();
    fd.append('shareable_type', props.item.type);
    fd.append('shareable_id', String(props.item.id));
    fd.append('shared_with_email', email.value.trim());
    fd.append('permission', permission.value);

    try {
        const res = await fetch(drive.shares.store.url(), {
            method: 'POST',
            credentials: 'same-origin',
            headers: jsonHeaders(),
            body: fd,
        });

        if (res.status === 422) {
            const data = await res.json();
            emailError.value =
                data.errors?.shared_with_email?.[0] ?? 'No se pudo compartir';
            return;
        }
        if (!res.ok) throw new Error(`HTTP ${res.status}`);

        toast.success(`Compartido con ${email.value.trim()}`);
        email.value = '';
        await refreshShares();
    } catch {
        toast.error('No se pudo compartir');
    } finally {
        submittingUser.value = false;
    }
}

async function generatePublicLink() {
    submittingPublic.value = true;
    const fd = new FormData();
    fd.append('shareable_type', props.item.type);
    fd.append('shareable_id', String(props.item.id));
    fd.append('public_link', '1');
    fd.append('permission', 'view');

    try {
        const res = await fetch(drive.shares.store.url(), {
            method: 'POST',
            credentials: 'same-origin',
            headers: jsonHeaders(),
            body: fd,
        });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        toast.success('Enlace público generado');
        await refreshShares();
    } catch {
        toast.error('No se pudo generar el enlace');
    } finally {
        submittingPublic.value = false;
    }
}

async function revoke(share: ShareRow) {
    const label = share.is_public_link
        ? 'el enlace público'
        : (share.shared_with?.email ?? 'este acceso');
    if (!confirm(`¿Revocar acceso de ${label}?`)) return;

    try {
        const res = await fetch(drive.shares.destroy.url({ share: share.id }), {
            method: 'DELETE',
            credentials: 'same-origin',
            headers: jsonHeaders(),
        });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        toast.success('Acceso revocado');
        await refreshShares();
    } catch {
        toast.error('No se pudo revocar');
    }
}

async function changePermission(share: ShareRow, perm: 'view' | 'edit') {
    if (share.permission === perm) return;
    const fd = new FormData();
    fd.append('_method', 'PATCH');
    fd.append('permission', perm);

    try {
        const res = await fetch(drive.shares.update.url({ share: share.id }), {
            method: 'POST',
            credentials: 'same-origin',
            headers: jsonHeaders(),
            body: fd,
        });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        await refreshShares();
    } catch {
        toast.error('No se pudo actualizar el permiso');
    }
}

function copyLink(url: string) {
    navigator.clipboard.writeText(url);
    toast.success('Copiado');
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="max-w-lg">
            <DialogHeader>
                <DialogTitle class="truncate">Compartir "{{ item.name }}"</DialogTitle>
                <DialogDescription>
                    Gestiona quién tiene acceso a este {{ item.type === 'folder' ? 'la carpeta' : 'archivo' }}.
                </DialogDescription>
            </DialogHeader>

            <!-- Quién tiene acceso -->
            <section class="space-y-2">
                <h3 class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                    Con acceso
                </h3>
                <div
                    v-if="loadingList"
                    class="flex items-center justify-center rounded-lg border bg-muted/30 p-4 text-sm text-muted-foreground"
                >
                    <Loader2 class="size-4 animate-spin" /> Cargando…
                </div>
                <p
                    v-else-if="shares.length === 0"
                    class="rounded-lg border bg-muted/30 p-4 text-sm text-muted-foreground"
                >
                    Solo tú tienes acceso.
                </p>
                <ul v-else class="space-y-2">
                    <li
                        v-for="share in shares"
                        :key="share.id"
                        class="flex items-center gap-3 rounded-lg border bg-card p-3"
                    >
                        <component
                            :is="share.is_public_link ? Globe : UserIcon"
                            class="size-5 shrink-0 text-muted-foreground"
                        />
                        <div class="min-w-0 flex-1">
                            <p v-if="share.is_public_link" class="truncate text-sm font-medium">
                                Enlace público
                            </p>
                            <template v-else>
                                <p class="truncate text-sm font-medium">{{ share.shared_with?.name }}</p>
                                <p class="truncate text-xs text-muted-foreground">
                                    {{ share.shared_with?.email }}
                                </p>
                            </template>
                        </div>

                        <div v-if="!share.is_public_link" class="flex gap-1">
                            <Button
                                type="button"
                                size="sm"
                                :variant="share.permission === 'view' ? 'default' : 'ghost'"
                                @click="changePermission(share, 'view')"
                            >Lectura</Button>
                            <Button
                                type="button"
                                size="sm"
                                :variant="share.permission === 'edit' ? 'default' : 'ghost'"
                                @click="changePermission(share, 'edit')"
                            >Edición</Button>
                        </div>
                        <span
                            v-else
                            class="rounded bg-muted px-2 py-0.5 text-xs"
                        >solo lectura</span>

                        <Button
                            type="button"
                            variant="ghost"
                            size="icon-sm"
                            @click="revoke(share)"
                            :title="'Revocar'"
                        >
                            <Trash2 class="size-4 text-destructive" />
                        </Button>
                    </li>
                </ul>

                <div
                    v-for="share in shares.filter((s) => s.is_public_link && s.public_url)"
                    :key="`url-${share.id}`"
                    class="flex items-center gap-2 rounded-lg border bg-muted/30 p-2"
                >
                    <Input :model-value="share.public_url ?? ''" readonly class="font-mono text-xs" />
                    <Button
                        type="button"
                        variant="outline"
                        size="icon"
                        @click="copyLink(share.public_url!)"
                    >
                        <Copy class="size-4" />
                    </Button>
                </div>
            </section>

            <!-- Compartir con usuario -->
            <section class="space-y-3 border-t pt-4">
                <h3 class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                    Compartir con un usuario
                </h3>
                <form @submit.prevent="shareWithUser" class="space-y-3">
                    <div class="space-y-1">
                        <Label for="share-email">Email</Label>
                        <Input
                            id="share-email"
                            type="email"
                            v-model="email"
                            placeholder="usuario@ejemplo.com"
                            required
                        />
                        <p v-if="emailError" class="text-sm text-destructive">{{ emailError }}</p>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex gap-1">
                            <Button
                                type="button"
                                size="sm"
                                :variant="permission === 'view' ? 'default' : 'outline'"
                                @click="permission = 'view'"
                            >Lectura</Button>
                            <Button
                                type="button"
                                size="sm"
                                :variant="permission === 'edit' ? 'default' : 'outline'"
                                @click="permission = 'edit'"
                            >Edición</Button>
                        </div>
                        <Button type="submit" :disabled="submittingUser || !email.trim()">
                            <Loader2 v-if="submittingUser" class="size-4 animate-spin" />
                            Compartir
                        </Button>
                    </div>
                </form>
            </section>

            <!-- Enlace público -->
            <section
                v-if="!shares.some((s) => s.is_public_link)"
                class="space-y-3 border-t pt-4"
            >
                <h3 class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                    Enlace público
                </h3>
                <p class="text-sm text-muted-foreground">
                    Cualquier persona con el enlace podrá ver este recurso.
                </p>
                <Button type="button" @click="generatePublicLink" :disabled="submittingPublic">
                    <Loader2 v-if="submittingPublic" class="size-4 animate-spin" />
                    <LinkIcon v-else class="size-4" />
                    Generar enlace
                </Button>
            </section>

            <DialogFooter>
                <Button type="button" variant="outline" @click="open = false">Cerrar</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
