<script>
document.addEventListener('DOMContentLoaded', function () {
    var personnelSelect = document.querySelector('[data-personnel-branch-source]');
    var branchSelect = document.querySelector('[data-personnel-branch-target]');
    if (!personnelSelect || !branchSelect) return;

    function applyPersonnelBranch() {
        var opt = personnelSelect.options[personnelSelect.selectedIndex];
        var branchId = opt && opt.getAttribute('data-branch-id') ? opt.getAttribute('data-branch-id') : '';
        if (branchId) branchSelect.value = branchId;
    }

    personnelSelect.addEventListener('change', applyPersonnelBranch);
});
</script>
