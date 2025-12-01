<?php $__env->startSection('title', 'Tambah Financial Highlight'); ?>

<?php $__env->startSection('content'); ?>
<div class="row">
    <div class="col-12">
        <!-- Header -->
        <div class="card mb-4">
            <div class="card-header">
                <h4 class="card-title mb-0">
                    <i class="ti ti-plus me-2"></i>
                    Tambah Financial Highlight
                </h4>
                <small class="text-muted">Input data indikator keuangan untuk periode tertentu</small>
            </div>
        </div>

        <!-- Form -->
        <div class="card">
            <div class="card-body">
                <form method="POST" action="<?php echo e(route('financial-highlights.store')); ?>">
                    <?php echo csrf_field(); ?>

                    <!-- Period Selection -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="period_year" class="form-label">Tahun</label>
                            <select class="form-control <?php $__errorArgs = ['period_year'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="period_year" name="period_year" required>
                                <option value="">Pilih Tahun</option>
                                <?php for($year = date('Y'); $year >= 2020; $year--): ?>
                                    <option value="<?php echo e($year); ?>" <?php echo e(old('period_year') == $year ? 'selected' : ''); ?>><?php echo e($year); ?></option>
                                <?php endfor; ?>
                            </select>
                            <?php $__errorArgs = ['period_year'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div class="col-md-6">
                            <label for="period_month" class="form-label">Bulan</label>
                            <select class="form-control <?php $__errorArgs = ['period_month'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="period_month" name="period_month" required>
                                <option value="">Pilih Bulan</option>
                                <?php for($month = 1; $month <= 12; $month++): ?>
                                    <option value="<?php echo e($month); ?>" <?php echo e(old('period_month') == $month ? 'selected' : ''); ?>>
                                        <?php echo e(date('F', mktime(0, 0, 0, $month, 1))); ?>

                                    </option>
                                <?php endfor; ?>
                            </select>
                            <?php $__errorArgs = ['period_month'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>

                    <!-- Financial Indicators -->
                    <div class="row">
                        <!-- CAR -->
                        <div class="col-md-6 mb-3">
                            <label for="car" class="form-label">CAR (Capital Adequacy Ratio)</label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" max="100"
                                       class="form-control <?php $__errorArgs = ['car'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                       id="car" name="car" value="<?php echo e(old('car')); ?>"
                                       placeholder="Contoh: 15.50">
                                <span class="input-group-text">%</span>
                            </div>
                            <small class="text-muted">Rasio kecukupan modal</small>
                            <?php $__errorArgs = ['car'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback d-block"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <!-- ROA -->
                        <div class="col-md-6 mb-3">
                            <label for="roa" class="form-label">ROA (Return on Assets)</label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="-100" max="100"
                                       class="form-control <?php $__errorArgs = ['roa'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                       id="roa" name="roa" value="<?php echo e(old('roa')); ?>"
                                       placeholder="Contoh: 2.30">
                                <span class="input-group-text">%</span>
                            </div>
                            <small class="text-muted">Return on Assets</small>
                            <?php $__errorArgs = ['roa'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback d-block"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <!-- ROE -->
                        <div class="col-md-6 mb-3">
                            <label for="roe" class="form-label">ROE (Return on Equity)</label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="-100" max="100"
                                       class="form-control <?php $__errorArgs = ['roe'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                       id="roe" name="roe" value="<?php echo e(old('roe')); ?>"
                                       placeholder="Contoh: 18.75">
                                <span class="input-group-text">%</span>
                            </div>
                            <small class="text-muted">Return on Equity</small>
                            <?php $__errorArgs = ['roe'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback d-block"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <!-- Aset (Calculated) -->
                        <div class="col-md-6 mb-3">
                            <label for="aset" class="form-label">Total Aset <span class="badge bg-info">Otomatis</span></label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" min="0" readonly
                                       class="form-control bg-light"
                                       id="aset" name="aset" value="<?php echo e(old('aset')); ?>"
                                       placeholder="Akan dihitung otomatis">
                            </div>
                            <small class="text-muted">Total aset (dihitung dari pembiayaan + DPK + estimasi aset lain)</small>
                        </div>

                        <!-- Pembiayaan (Calculated) -->
                        <div class="col-md-6 mb-3">
                            <label for="pembiayaan" class="form-label">Total Pembiayaan <span class="badge bg-info">Otomatis</span></label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" min="0" readonly
                                       class="form-control bg-light"
                                       id="pembiayaan" name="pembiayaan" value="<?php echo e(old('pembiayaan')); ?>"
                                       placeholder="Akan dihitung otomatis">
                            </div>
                            <small class="text-muted">Total outstanding pembiayaan dari database</small>
                        </div>

                        <!-- Laba Rugi -->
                        <div class="col-md-6 mb-3">
                            <label for="laba_rugi" class="form-label">Laba/Rugi</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number"
                                       class="form-control <?php $__errorArgs = ['laba_rugi'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                       id="laba_rugi" name="laba_rugi" value="<?php echo e(old('laba_rugi')); ?>"
                                       placeholder="Contoh: 25000000">
                            </div>
                            <small class="text-muted">Laba bersih (positif) atau rugi (negatif)</small>
                            <?php $__errorArgs = ['laba_rugi'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback d-block"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <!-- DPK (Calculated) -->
                        <div class="col-md-6 mb-3">
                            <label for="dpk" class="form-label">DPK (Dana Pihak Ketiga) <span class="badge bg-info">Otomatis</span></label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" min="0" readonly
                                       class="form-control bg-light"
                                       id="dpk" name="dpk" value="<?php echo e(old('dpk')); ?>"
                                       placeholder="Akan dihitung otomatis">
                            </div>
                            <small class="text-muted">Dana Pihak Ketiga (tabungan + deposito)</small>
                        </div>

                        <!-- FDR (Calculated) -->
                        <div class="col-md-6 mb-3">
                            <label for="fdr" class="form-label">FDR (Financing to Deposit Ratio) <span class="badge bg-info">Otomatis</span></label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" max="500" readonly
                                       class="form-control bg-light <?php $__errorArgs = ['fdr'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                       id="fdr" name="fdr" value="<?php echo e(old('fdr')); ?>"
                                       placeholder="Akan dihitung otomatis">
                                <span class="input-group-text">%</span>
                            </div>
                            <small class="text-muted">Rasio pembiayaan terhadap deposito (dihitung otomatis)</small>
                            <?php $__errorArgs = ['fdr'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback d-block"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <!-- NPF (Calculated) -->
                        <div class="col-md-6 mb-3">
                            <label for="npf" class="form-label">NPF (Non-Performing Financing) <span class="badge bg-info">Otomatis</span></label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" max="100" readonly
                                       class="form-control bg-light"
                                       id="npf" name="npf" value="<?php echo e(old('npf')); ?>"
                                       placeholder="Akan dihitung otomatis">
                                <span class="input-group-text">%</span>
                            </div>
                            <small class="text-muted">Non-Performing Financing ratio (dihitung dari database)</small>
                        </div>

                        <!-- BOPO -->
                        <div class="col-md-6 mb-3">
                            <label for="bopo" class="form-label">BOPO (Biaya Operasional vs Pendapatan Operasional)</label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" max="200"
                                       class="form-control <?php $__errorArgs = ['bopo'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                       id="bopo" name="bopo" value="<?php echo e(old('bopo')); ?>"
                                       placeholder="Contoh: 78.90">
                                <span class="input-group-text">%</span>
                            </div>
                            <small class="text-muted">Efficiency ratio</small>
                            <?php $__errorArgs = ['bopo'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback d-block"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="d-flex justify-content-between mt-4">
                        <a href="<?php echo e(route('financial-highlights.index')); ?>" class="btn btn-secondary">
                            <i class="ti ti-arrow-left me-1"></i>
                            Kembali
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-device-floppy me-1"></i>
                            Simpan Data
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const periodYear = document.getElementById('period_year');
    const periodMonth = document.getElementById('period_month');

    function calculateDerivedValues() {
        const year = periodYear.value;
        const month = periodMonth.value;

        if (year && month) {
            // Calculate derived values via AJAX
            fetch(`/api/financial-highlights/calculate?year=${year}&month=${month}`)
                .then(response => response.json())
                .then(data => {
                    if (data.dpk !== undefined) {
                        document.getElementById('dpk').value = data.dpk;
                    }
                    if (data.pembiayaan !== undefined) {
                        document.getElementById('pembiayaan').value = data.pembiayaan;
                    }
                    if (data.npf !== undefined) {
                        document.getElementById('npf').value = data.npf;
                    }
                    if (data.aset !== undefined) {
                        document.getElementById('aset').value = data.aset;
                    }
                    if (data.fdr !== undefined) {
                        document.getElementById('fdr').value = data.fdr;
                    }
                })
                .catch(error => {
                    console.error('Error calculating derived values:', error);
                });
        }
    }

    // Calculate when period changes
    periodYear.addEventListener('change', calculateDerivedValues);
    periodMonth.addEventListener('change', calculateDerivedValues);
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ajspryn/Project/finboard/resources/views/financial-highlights/create.blade.php ENDPATH**/ ?>