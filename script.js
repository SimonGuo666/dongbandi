function showMatchDetails(matchId) {
    window.location.href = `match_details.html?id=${matchId}`;
}
// 页面加载时查询用户已点赞的评论
window.onload = function() {
    // 获取当前新闻的评论ID
    const newsId = new URLSearchParams(window.location.search).get('id');
    
    fetch('get_liked_comments.php')  
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const likedComments = data.liked_comments;
                
                likedComments.forEach(commentId => {
                    const likeButton = document.querySelector(`#like-btn-${commentId}`);
                    if (likeButton) {
                        // 更新点赞按钮为已赞
                        likeButton.innerText = '已赞';
                        likeButton.disabled = true;  // 禁用按钮
                    }
                });
            }
        })
        .catch(error => {
            console.error('请求失败:', error);
        });
};

document.querySelectorAll('.like-btn').forEach(button => {
    button.addEventListener('click', function() {
        let commentId = parseInt(this.getAttribute('data-comment-id'));  // 将 commentId 转换为整数

        // 如果已赞，避免重复点击
        if (this.innerText === '已赞') {
            return;
        }

        // 更改按钮文字为 "已赞"
        this.innerText = '已赞';  
        this.disabled = true;  // 禁用按钮，防止再次点赞

        // 发送请求更新数据库中的点赞数
        fetch(`like_comment.php?comment_id=${commentId}`, {
            method: 'GET'
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('点赞成功');
                    // 点赞成功后刷新页面
                    window.location.reload();
                } else {
                    console.log('点赞失败');
                    // 如果点赞失败，恢复按钮状态
                    this.innerText = '点赞';
                    this.disabled = false;
                }
            })
            .catch(error => {
                console.error('点赞失败:', error);
                // 如果请求失败，恢复按钮状态
                this.innerText = '点赞';
                this.disabled = false;
            });
    });
});