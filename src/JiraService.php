<?php

namespace LotTransmitter;

use JiraRestApi\Configuration\ArrayConfiguration;
use JiraRestApi\Issue\IssueField;
use JiraRestApi\Issue\IssueService;
use JiraRestApi\JiraException;
use JsonMapper_Exception;

/**
 * Class JiraService
 *
 * PHP Version 7
 *
 * @category  PHP
 * @package   Jira
 * @author    privatrepo Trade GmbH <development@privatrepo.ag>
 * @copyright 2016-2017 privatrepo Trade GmbH
 * @license   Proprietary http://www.privatrepo.ag
 */
class JiraService
{
    public const PRIORITY_BLOCKER = 'Blocker';
    public const PRIORITY_CRITICAL = 'Critical';
    public const PRIORITY_HIGH = 'High';
    public const PRIORITY_MEDIUM = 'Medium';
    public const PRIORITY_LOW = 'Low';

    /** @var IssueService */
    protected $issueService;
    /** @var array */
    protected $config;

    /**
     * JiraService constructor.
     * @param array $config
     * @throws JiraException
     */
    public function __construct(array $config)
    {
        $this->issueService = new IssueService(
            new ArrayConfiguration(
                [
                    'jiraHost'     => $config['host'],
                    'jiraUser'     => $config['username'],
                    'jiraPassword' => $config['password'],
                ]
            )
        );
        $this->config = $config;
    }

    /**
     * @param $headLine
     * @param $description
     * @param null $priority
     *
     * @return string|null
     * @throws JsonMapper_Exception
     *
     * @throws JiraException
     */
    public function createNewTicket($headLine, $description, $priority = null): ?string
    {
        if (isset($this->config['enable']) && $this->config['enable'] === false) {
            return null;
        }
        if (!$this->config['enableCreateTicket']) {
            return null;
        }

        $issue = new IssueField();
        $issue->setProjectKey($this->config['project']);
        $issue->setSummary($headLine);
        $issue->setAssigneeName('Unassigned');

        if ($priority === null) {
            $issue->priority = null;
        }

        $issue->setIssueType($this->config['issueType']);
        $issue->setDescription($description);
        $issue->addLabel(getenv('ENVIRONMENT'));

        $issue->assignee = null;

        $issue = $this->issueService->create($issue);

        return $issue->key;
    }
}