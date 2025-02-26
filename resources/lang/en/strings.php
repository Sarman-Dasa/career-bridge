<?php


return [

    //Auth Message
    "auth" => [
        "register" => "User registered successfully.",
        "register_error" => "Error in registering user.",
        "email_token_invalid" => "Email id or token is invalid.",
        "already_verified" => "Email is already verified.",
        "link_expired" => "The verification link has expired.",
        "verified" => "Your email is verified.",
        "account_delete" => "Your account has been deleted.",
        "verify_email" => "Please verify your email in order to sign in to the platform.",
        "accept_invitation" => "Please accept an invitation in order to login.",
        "login" => "Signed in successfully.",
        "invalid_credential" => "The credentials are invalid.",
        "reset_token_notfound" => "User/Password reset token not found.",
        "old_password_invalid" => "The old password does not match our records.",
        "reset_password_token" => "Reset password token generated successfully.",
        "reset_password" => "Password reset successfully.",
        "change_password" => "Password changed successfully.",
        "reset_failed" => "Password reset failed.",
        "logout" => "Logout successful.",
        "reset_link_sent" => "Password reset link sent successfully to your registered email address.",
        "email_unregistered" => "The entered email is unregistered.",
        "account_deactivate" => "Your account is deactivated.",
        "email_already_taken" => "The email has already been taken.",
        "already_reset_password" => "You have already reset your password using this link, Please again request in order to reset your password again.",
    ],

    "connection" => [
        "create" => "Connection request sent.",
        "self_connection_error" => "You cannot accept a request you sent yourself.",
        "not_found" => "No connection request found.",
        "update" => "Connection status updated.",
        "delete" => "Connection removed.",
        "no_pending_requests" => "No pending connection requests.",
        "pending_requests" => "Pending connection request list.",
        "suggested_connections"      => "suggested connection list.",
    ],

    "post" => [
        "create" => "Post uploaded successfully.",
        "update" => "Post updated successfully.",
        "delete" => "Post deleted successfully.",
        "like_post" => "Post liked successfully",
        "dislike_post" => "Post dislike successfully.",
        "user_post_list" => "User post list"
    ],

    "comment" => [
        "create" => "Comment added successfully.",
        "update" => "Comment updated successfully.",
        "delete" => "Comment deleted successfully.",
        "like" => "Comment liked successfully",
        "dislike" => "Comment dislike successfully.",
        "disabled_comment"     => "Comments are disabled for this post",
        "no_permission_create"        => "You do not have permission to comment on this post",
        "no_permission_update"        => "You do not have permission to update this comment",
        "no_access"                   => "Access denied"
    ],

    "user" => [
        "list" => "User list.",
        "update_profile" => "Profile update successfully.",
        "delete_account" => "Account delete successfully."
    ],

    "message" => [
        "sent" => "Message sent successfully.",
        "list" => "Message list.",
        "update" => "Message updated successfully.",
        "delete" => "Message deleted successfully."
    ],

    "group" => [
        'list' => 'Groups retrieved successfully',
        'created' => 'Group created successfully',
        'updated' => 'Group updated successfully',
        'deleted' => 'Group deleted successfully',
        'members_added' => 'Members added successfully',
        'member_removed' => 'Member removed successfully',
        'role_updated' => 'Member role updated successfully',
        'not_admin' => 'You are not an admin of this group',
        'last_admin' => 'Cannot remove the last admin from the group',
    ],
];
