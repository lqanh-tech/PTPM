<?php

if (!isset($searchFormId)) $searchFormId = 'search-form';
if (!isset($tableBodyId)) $tableBodyId = 'data-list';
if (!isset($placeholderText)) $placeholderText = 'Tìm kiếm...';

?>
<div class="search-box">
    <form id="<?php echo $searchFormId; ?>" class="search-form" onsubmit="return false;">
        <input type="text" id="<?php echo $searchFormId; ?>-input" placeholder="<?php echo $placeholderText; ?>" />
        <button type="button" onclick="performTableSearch('<?php echo $searchFormId; ?>', '<?php echo $tableBodyId; ?>')">
            <i class="fas fa-search"></i> Tìm kiếm
        </button>
    </form>
</div>

<div id="no-results-message" class="no-results-message">
    Không tìm thấy kết quả phù hợp
</div>

<!-- Include the necessary JS and CSS only once -->
<?php if (!isset($searchScriptsIncluded)): ?>
    <link rel="stylesheet" href="./css_LQA/table-search.css">
    <script src="./js_LQA/table-search.js"></script>
    <?php $searchScriptsIncluded = true; ?>
<?php endif; ?>

<script>

    document.addEventListener('DOMContentLoaded', function() {
        setupTableSearch('<?php echo $searchFormId; ?>', '<?php echo $tableBodyId; ?>', '<?php echo $placeholderText; ?>');
    });

    function performTableSearch(formId, tableBodyId) {
        const searchInput = document.getElementById(formId + '-input');
        const query = searchInput.value.toLowerCase().trim();
        const tableBody = document.getElementById(tableBodyId);
        const rows = tableBody.querySelectorAll('tr');
        let matchCount = 0;

        rows.forEach(function(row) {
            const text = row.textContent.toLowerCase();
            if (text.includes(query)) {
                row.style.display = '';
                matchCount++;
            } else {
                row.style.display = 'none';
            }
        });

        const noResultsMessage = document.getElementById('no-results-message');
        if (matchCount === 0 && query !== '') {
            noResultsMessage.classList.add('show');
        } else {
            noResultsMessage.classList.remove('show');
        }
    }
</script>