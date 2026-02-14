// Main JS
document.addEventListener('DOMContentLoaded', () => {
    // Reply functionality
    const replyButtons = document.querySelectorAll('.reply-btn');
    const commentFormContainer = document.getElementById('comment-form-container');
    const parentIdInput = document.getElementById('parent_id');
    const cancelReplyBtn = document.getElementById('cancel-reply');
    const originalFormParent = commentFormContainer ? commentFormContainer.parentNode : null;

    if (replyButtons && commentFormContainer) {
        replyButtons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const commentId = btn.getAttribute('data-id');
                parentIdInput.value = commentId;
                
                // Move form after the comment
                const commentDiv = btn.closest('.comment');
                commentDiv.appendChild(commentFormContainer);
                
                cancelReplyBtn.style.display = 'inline-block';
            });
        });

        cancelReplyBtn.addEventListener('click', (e) => {
            e.preventDefault();
            parentIdInput.value = '';
            originalFormParent.appendChild(commentFormContainer);
            cancelReplyBtn.style.display = 'none';
        });
    }
});
