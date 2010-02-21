<script>
    function newPage() {
        var page = prompt('New Page', 'Page Title');
        page = page.replace(/\s+/, '_');
        window.location = 'http://hexten.net/wiki/index.php/' + page;
    }
</script>
<a href="javascript:newPage()">New Page</a>