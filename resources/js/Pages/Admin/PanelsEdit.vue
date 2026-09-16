<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    panel: { type: Object, required: true }
});

const form = useForm({
    tag: props.panel.tag || '',
    title: props.panel.title || '',
    desc_1: props.panel.description || ''
});

const submit = () => {
    form.put(`/admin/panels/${props.panel.id}`, {
        preserveScroll: true
    });
};
</script>

<template>
    <Head title="Silahkan Edit" />

    <div class="admin-container">
        <div class="wrap-form">
            
            <div class="header-flex">
                <Link href="/admin/about" class="btn-back"><i class='bx bx-arrow-back'></i> Kembali ke Tentang Saya</Link>
            </div>

            <div class="card-form">
                <!-- Header Form Pengganti Preview -->
                <div class="form-header">
                    <div class="icon-box"><i class='bx bx-edit-alt'></i></div>
                    <div class="header-text">
                        <h2>Edit Panel Cerita</h2>
                        <p>Perbarui informasi tag, judul, dan isi deskripsi panel ini.</p>
                    </div>
                </div>
                
                <form @submit.prevent="submit" class="form-body">
                    <div class="grid2">
                        <div class="form-group">
                            <label>Tag Panel</label>
                            <input type="text" v-model="form.tag" required placeholder="Misal: 03 / VISI">
                        </div>
                        <div class="form-group">
                            <label>Judul Panel</label>
                            <input type="text" v-model="form.title" required placeholder="Fokus & Tujuan">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Isi Deskripsi Panel</label>
                        <textarea v-model="form.desc_1" rows="7" required placeholder="Tuliskan isi cerita panel di sini..."></textarea>
                    </div>
                    
                    <div class="action-footer">
                        <Link href="/admin/about" class="btn-cancel">Batal</Link>
                        <button type="submit" class="btn-save" :disabled="form.processing">
                            <i :class="form.processing ? 'bx bx-loader-alt bx-spin' : 'bx bx-save'"></i> 
                            {{ form.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</template>

<style scoped>
.admin-container {
    --bg: #0A0E17; --panel: #10151F; --panel-2: #141B29; --line: #232D3E; 
    --text: #EAEEF5; --dim: #8792A6; --primary: #3763E0; --cyan: #4E9BE0; 
    background-color: var(--bg); color: var(--text); font-family: 'Inter', sans-serif;
    min-height: 100vh; padding: 40px; box-sizing: border-box;
}

.wrap-form { max-width: 750px; margin: 0 auto; padding-bottom: 60px; }

.header-flex { margin-bottom: 24px; }
.btn-back { display: inline-flex; align-items: center; gap: 6px; color: var(--dim); font-size: 13.5px; font-weight: 500; text-decoration: none; transition: 0.3s;}
.btn-back:hover { color: var(--cyan); transform: translateX(-5px);}

.card-form { background: var(--panel); border: 1px solid var(--line); border-top: 3px solid var(--cyan); border-radius: 20px; overflow: hidden; box-shadow: 0 15px 40px rgba(0,0,0,0.3); }

/* Header Form Style */
.form-header { display: flex; align-items: center; gap: 20px; padding: 30px 40px; background: linear-gradient(to bottom, rgba(78, 155, 224, 0.05), transparent); border-bottom: 1px solid var(--line); }
.icon-box { width: 50px; height: 50px; border-radius: 14px; background: rgba(78, 155, 224, 0.1); color: var(--cyan); display: flex; align-items: center; justify-content: center; font-size: 24px; border: 1px solid rgba(78, 155, 224, 0.2); }
.header-text h2 { font-family: 'Sora', sans-serif; font-size: 18px; margin: 0 0 4px 0; color: #fff; font-weight: 700;}
.header-text p { font-size: 13px; color: var(--dim); margin: 0; }

.form-body { padding: 40px; }

.form-group { margin-bottom: 24px; }
label { font-size: 11.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; color: var(--dim); display: block; margin-bottom: 10px; }

input[type=text], textarea { width: 100%; padding: 15px 18px; background: var(--bg); border: 1px solid var(--line); border-radius: 12px; color: var(--text); font-size: 14px; transition: 0.3s; box-sizing: border-box;}
input:focus, textarea:focus { outline: none; border-color: var(--cyan); box-shadow: 0 0 0 3px rgba(78,155,224,0.1); background: var(--panel-2);}
.grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
@media (max-width: 600px) { .grid2 { grid-template-columns: 1fr; } }

.action-footer { display: flex; justify-content: flex-end; align-items: center; gap: 16px; margin-top: 40px; padding-top: 30px; border-top: 1px dashed var(--line); }
.btn-cancel { padding: 14px 28px; background: transparent; border: 1px solid var(--line); color: var(--text); border-radius: 12px; font-size: 14px; font-weight: 600; text-decoration: none; transition: 0.3s; }
.btn-cancel:hover { background: rgba(255,255,255,0.05); border-color: var(--dim); }

.btn-save { padding: 14px 36px; border: none; border-radius: 12px; cursor: pointer; background: var(--cyan); color: #000; font-size: 14px; font-weight: 700; transition: 0.3s; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 15px rgba(78, 155, 224, 0.2);}
.btn-save:hover:not(:disabled) { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(78, 155, 224, 0.4); background: #5bb0ff; }
.btn-save:disabled { opacity: 0.7; cursor: not-allowed; }
</style>