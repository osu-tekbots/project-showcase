<?php
namespace Email;

/**
 * Implements logic for sending emails relating to project collaboration, such as invites, confirmations, and
 * notifications.
 */
class CollaborationMailer extends Mailer {

    /** @var \Util\ConfigManager */
    private $config;

    /**
     * Constructs a new instance of the mailer.
     *
     * @param string $from the from address for emails
     * @param string $subjectTag an optional subject tag to prefix the provided subject tag with
     * @param \Util\ConfigurationManager $config configuration that allows us to construct links to resources sent
     * in the email.
     */
    public function __construct($from, $subjectTag, $logger, $config) {
        parent::__construct($from, $subjectTag, $logger);
        $this->config = $config;
    }

    /**
     * Sends an email inviting a user to collaborate on a project.
     *
     * @param \Model\User $fromUser the user to send the invite to
     * @param \Model\User|string $toUser the user (or user email address) to send the invite to
     * @param \Model\ShowcaseProject $project the project the user is invited to collaborate on
     * @return boolean true if the email is sent successfully, false otherwise
     */
    public function sendInvite($fromUser, $toUser, $project) {
        $isString = \is_string($toUser);

        $fromUserName = $fromUser->getFullName();
        $toUserName = !$isString ? ' ' . $toUser->getFullName() : '';
        $toEmail = !$isString ? $toUser->getEmail() : $toUser;
        $projectId = $project->getId();
        $projectTitle = $project->getTitle();
        $projectDescription = $project->getDescription();
        if (\strlen($projectDescription) > 300) {
            $projectDescription = \substr($projectDescription, 0, 300) . '...';
        }

        $relativeLink = "projects/invite/?pid=$projectId";

        $link = $this->getAbsoluteUrlTo($relativeLink);

        $subject = "Invitation to Join $projectTitle";

        $message = "
        <p>Hello$toUserName,</p>

        <p>$fromUserName has invited you to join a showcase project as a collaborator. See the information below for
        details:</p>

        <table>
            <tr>
                <th>Title</th>
                <td>$projectTitle</td>
            </tr>
            <tr>
                <th>Description</th>
                <td>$projectDescription</td>
            </tr>
            <tr>
                <td colspan='2'>
                    <a href='$link' class='button'>
                        View Invitation
                    </a>
                </td>
            </tr>
        </table>

        <p>
        Project Showcase Team<br/>
        Oregon State University
        </p>
        ";

        $message = $this->prependStylesTo($message);

        return $this->sendEmail($toEmail, $subject, $message, true);
    }

    /**
     * Applies CSS styling to an HTML formatted email body.
     *
     * @param string $message the HTML email message body to apply the styles to
     * @return string the message with CSS styles applied
     */
    private function prependStylesTo($message) {
        return '
        <style>
        table {
            margin: 10px;
        }

        table th,
        table td {
            padding: 10px;
            border: 1px solid #ccc;
        }

        .button {
            cursor: pointer;
            text-decoration: none;
            color: white;
            padding: 5px;
            margin: 10px;
            background-color: #dc4405;
        }
        .button:hover {
            text-decoration: none;
        }
        </style>
        ' . $message;
    }

    /**
     * Uses configuration to construct an absolute URL to a resource
     *
     * @param string $path the relative URL
     * @return string the absoulte URL
     */
    private function getAbsoluteUrlTo($path) {
        return $this->config->getBaseUrl() . $path;
    }
}
