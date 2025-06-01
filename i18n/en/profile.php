<?php

return [
    "edit" => [
        "title" => "Edit Profile",

        "main" => [
            "title"       => "Main Settings",
            "description" => "Here you can change the main settings of your account.",
            "info_title"  => "Basic Information",
            "info_description" => "Some data may be visible to other users.",

            "fields" => [
                "name"                  => "Name",
                "email"                 => "Email",
                "password"              => "Password",
                "email_verified"        => "Email verified",
                "email_not_verified"    => "Email not verified",
                "password_not_set"      => "Not set",
                "password_not_provided" => "Not provided",
                "last_changed"          => "Last changed",
                "verify_email"          => "Verify email",
            ],

            "password_description" => "A strong password helps protect your account.",

            "basic_information" => [
                "title"       => "Basic Information",
                "description" => "Change the basic information of your profile.",

                "fields" => [
                    "name"                 => "Name",
                    "name_placeholder"     => "Enter your full name",
                    "name_info"            => "This name will be visible to all users on the site",

                    "login"                => "Username",
                    "login_placeholder"    => "Enter your username",
                    "login_info"           => "Your username is visible only to you and is used to log in",

                    "uri"                  => "Profile URL",
                    "uri_placeholder"      => "Enter your URL",
                    "uri_info"             => "Enter the slug for your profile URL. For example: :example",

                    "email"                => "Email",
                    "email_placeholder"    => "Enter your email address",
                ],

                "save_changes"         => "Save changes",
                "save_changes_success" => "Basic information updated successfully.",
            ],

            "profile_images" => [
                "title"       => "Profile Images",
                "description" => "Upload your avatar and banner to personalize your profile.",

                "fields" => [
                    "avatar" => "Avatar",
                    "banner" => "Banner",
                ],

                "save_changes"         => "Save images",
                "save_changes_success" => "Profile images updated successfully.",
            ],

            "change_password" => [
                "title"       => "Change Password",
                "description" => "Change your current password for enhanced security.",

                "fields" => [
                    "current_password"                => "Current password",
                    "current_password_placeholder"    => "Enter current password",

                    "new_password"                    => "New password",
                    "new_password_placeholder"        => "Enter new password",

                    "confirm_new_password"            => "Confirm new password",
                    "confirm_new_password_placeholder"=> "Repeat new password",
                ],

                "save_changes"         => "Change password",
                "save_changes_success" => "Password changed successfully.",
                "current_password_incorrect" => "Current password is incorrect.",
                "passwords_do_not_match"      => "Passwords do not match.",
            ],

            "delete_account" => [
                "title"       => "Delete Account",
                "description" => "Deleting your account will result in permanent loss of all your data.",
                "confirm_message" => "Are you sure you want to delete your account? All your data will be permanently removed.",

                "fields" => [
                    "confirmation"             => "Deletion confirmation",
                    "confirmation_placeholder" => "Enter your username to confirm",
                ],

                "delete_button"       => "Delete Account",
                "delete_success"      => "Your account has been deleted successfully.",
                "delete_failed"       => "Incorrect confirmation. Account was not deleted.",
                "confirmation_error"  => "Please enter your username correctly.",
            ],

            "profile_privacy" => [
                "title"       => "Profile Privacy",
                "description" => "Configure your profile privacy settings.",

                "fields" => [
                    "hidden"  => [
                        "label" => "Public",
                        "info"  => "Your profile is visible to all users.",
                    ],
                    "visible" => [
                        "label" => "Private",
                        "info"  => "Your profile is hidden from other users.",
                    ],
                ],

                "save_changes_success" => "Privacy settings updated successfully.",
            ],

            "profile_theme" => [
                "title"       => "System Theme",
                "description" => "Select the theme for the entire system.",

                "fields" => [
                    "light" => [
                        "label" => "Light theme",
                        "info"  => "Suitable for daytime.",
                    ],
                    "dark"  => [
                        "label" => "Dark theme",
                        "info"  => "Ideal for working at night.",
                    ],
                    "system" => [
                        "label" => "System theme",
                        "info"  => "The theme will be automatically selected based on your device.",
                    ],
                ],

                "save_changes"         => "Save theme",
                "save_changes_success" => "Profile theme updated successfully.",
            ],
        ],

        "settings" => [
            "title" => "Settings",
        ],

        "social" => [
            "title"               => "Integrations",
            "description"         => "Connect social networks for quick login and access to additional features.",
            "unlink"              => "Unlink",
            "unlink_description"  => "Are you sure you want to unlink this social network?",
            "default_link"        => "Default link",
            "connect"             => "Link",
            "no_socials"          => "Unfortunately, there are no social networks in our system 😢",
            "show_description"    => "Show social network to other users",
            "hide_description"    => "Hide social network from other users",
            "last_social_network" => "To unlink a social network, set a password.",
        ],

        "payments" => [
            "title"       => "Payments",
            "description" => "History of payments and transactions.",
            "table"       => [
                "id"          => "ID",
                "date"        => "Date",
                "gateway"     => "Payment method",
                "amount"      => "Amount",
                "status"      => "Status",
                "promo"       => "Promo code",
                "transaction" => "Transaction",
                "actions"     => "Actions",
            ],
            "status" => [
                "paid"    => "Paid",
                "pending" => "Pending",
            ],
        ],

        "upload_directory_error" => "Upload directory does not exist.",
        "upload_failed"          => "Failed to upload :field.",
    ],

    "protection_warning"        => "Set a password to protect your account. <a href=\":link\">Set it</a>",
    "no_profile_modules_info"   => "No profile modules are installed in Flute. <a href=\":link\">View on marketplace</a>",
    "was_online"                => "Was online :date",
    "view"                      => "View profile",
    "social_deleted"            => "Social network unlinked successfully!",
    "member_since"              => "Member since :date",
    "hidden_warning"            => "Your profile is hidden from other users.",
    "profile_hidden"            => "This profile is hidden from other users.",
    "verification_warning"      => "Verify your email address to access additional features. <a href=\":link\">Verify</a>",
];
