import net.sf.json.JSONArray;
import net.sf.json.JSONObject;

node('docker') {
  properties([disableConcurrentBuilds()])

  PROJECT  = 'lot-transmitter'
  PROJECT_VERSION = '0.0.1'
  env.KUBECONFIG = '/root/.kube/sDEV'

  env.AWS_REGION = "eu-central-1"
  env.AWS_VERSION = "latest"

  BRANCH_NAME = BRANCH_NAME.toLowerCase()

  // Sentry
  env.SENTRY_ORG = "privatrepo-trade-gmbh"
  withCredentials([string(credentialsId: 'sentry-auth-token', variable: 'SENTRY_AUTH_TOKEN')]) {
      env.SENTRY_AUTH_TOKEN = SENTRY_AUTH_TOKEN
  }
  env.SENTRY_PROJECT_ID = "--"
  env.SENTRY_PUBLIC_KEY = "--"
  env.SENTRY_PROJECT = PROJECT

  NAMESPACE = "sl-${PROJECT}-${BRANCH_NAME}"
  env.NAMESPACE = NAMESPACE

  if ( BRANCH_NAME ==~ "^pr-.*" ) {
    currentBuild.result = 'ABORTED'
    error('This project does not support PRs')
  }

  // Jira
  env.JIRA_ENABLE_CREATE_TICKET = true
  env.JIRA_ENABLE = true
  env.JIRA_HOST = "https://jira.privatrepo.ag"
  env.JIRA_USERNAME = "monitoring"
  withCredentials([string(credentialsId: 'jira-monitoring-pass', variable: 'JIRA_PASSWORD')]) {
    env.JIRA_PASSWORD = JIRA_PASSWORD
  }
  env.JIRA_PROJECT = "EDI"
  env.JIRA_ISSUETYPE = "Bug"

  if( ! (BRANCH_NAME ==~ "master") ) {
    env.ENVIRONMENT = "branch-${BRANCH_NAME}"
    env.REST_ENDPOINT_URL = "http://172.27.120.68:8080/api/bo/lot"
    env.S3_PREFIX = "${BRANCH_NAME}"
  } else {
    env.ENVIRONMENT = "stage"
    env.REST_ENDPOINT_URL = "http://172.27.120.62:8080/api/bo/lot"
    env.S3_PREFIX = ""
  }


  env.TF_VAR_S3_POSTFIX = "-stage"

  PHP_DEV_IMAGE = "docker-registry.privatrepo.ag/sl-php/7.3/fpm-dev/privatrepo:0.2.0"
  env.PHP_DEV_IMAGE = PHP_DEV_IMAGE
  PHP_BASE_IMAGE = "docker-registry.privatrepo.ag/sl-php/7.3/fpm/privatrepo:0.2.0"
  env.PHP_BASE_IMAGE = PHP_BASE_IMAGE
  PHP_IMAGE = "docker-registry.privatrepo.ag/${PROJECT}/php"
  env.PHP_IMAGE = PHP_IMAGE

  def SLACK_CHANNEL = "lrs-notifications"

  stage('Prolog') {
    parallel version: {
      checkVersions()
    },
    "check branch name": {
      if( ! checkBranchName(NAMESPACE) ) {
        currentBuild.result = 'ABORTED'
        error('Namespace with branch name must match /^[a-zA-Z0-9-]+$/ and cannot be longer then 50 chars')
      }
    },
    images: {
      sh 'docker images'
    },
    environment: {
      sh 'ls -la /root'
      sh 'env | sort'
    },
    terraform: {
      sh 'terraform --version'
    },
    kubectl: {
      checkKubernetes()
    }
  }

  slackSend channel: SLACK_CHANNEL,
            attachments: slackMessage("good", "Build started").toString()

  try {
    stage('Preparation') {
      parallel checkout: {
        checkout scm
      },
      login: {
        withCredentials([usernamePassword(credentialsId: 'docker-registry', passwordVariable: 'REGISTRY_ACCESS_PSW', usernameVariable: 'REGISTRY_ACCESS_USR')]) {
          sh 'docker login -u $REGISTRY_ACCESS_USR -p $REGISTRY_ACCESS_PSW docker-registry.privatrepo.ag'
        }
      }
    }

    def COMMIT_HASH = sh(returnStdout: true, script: 'git rev-parse --short HEAD').trim() 
    env.COMMIT_HASH = COMMIT_HASH

    stage('Install php dependencies') {
      bitbucketStatusNotify(
        buildState: 'INPROGRESS',
        buildName: 'PHP dependencies',
        buildDescription: 'Install the development dependencies',
      )
      try {
        withCredentials([string(credentialsId: 'packagist-token', variable: 'PACKAGIST_TOKEN')]) {
          sh 'docker run -v ${PWD}:/var/www/html ${PHP_DEV_IMAGE} /bin/sh -c \"composer config --global --auth http-basic.repo.packagist.com token $PACKAGIST_TOKEN && composer install --classmap-authoritative --no-progress\"'
        }
      } catch (Exception e) {
        bitbucketStatusNotify(
          buildState: 'FAILED',
          buildName: 'PHP dependencies',
          buildDescription: 'Install the development dependencies',
        )
        throw e
      }
      bitbucketStatusNotify(
        buildState: 'SUCCESSFUL',
        buildName: 'PHP dependencies',
        buildDescription: 'Install the development dependencies',
      )
    }

    stage('Images') {
      bitbucketStatusNotify(
        buildState: 'INPROGRESS',
        buildName: 'Build docker image',
        buildDescription: 'Build the docker image for deployment',
      )
      try {
        parallel "PHP": {
          dir('devops/kubernetes/images/') {
            sh "cat Dockerfile.template | envsubst > Dockerfile"
          }

          sh "docker build -t ${PHP_IMAGE}:${COMMIT_HASH} -f devops/kubernetes/images/Dockerfile ."
          sh "docker push ${PHP_IMAGE}:${COMMIT_HASH}"
          if( BRANCH_NAME ==~ "master" ) {
            sh "docker tag ${PHP_IMAGE}:${COMMIT_HASH} ${PHP_IMAGE}:latest"
            sh "docker push ${PHP_IMAGE}:latest"
          }
        },
        "nginx": {
          echo "nothing to do"
        }
      } catch (Exception e) {
        bitbucketStatusNotify(
          buildState: 'FAILED',
          buildName: 'Build docker image',
          buildDescription: 'Build the docker image for deployment',
        )
        throw e
      }
      bitbucketStatusNotify(
        buildState: 'SUCCESSFUL',
        buildName: 'Build docker image',
        buildDescription: 'Build the docker image for deployment',
      )
    }

    stage('Infrastructure') {
      bitbucketStatusNotify(
        buildState: 'INPROGRESS',
        buildName: 'Infrastructure',
        buildDescription: 'Build the AWS part of the infrastructure',
      )
      try {
        sh 'env | sort'
        withCredentials([string(credentialsId: 'AWS_ACCESS_KEY_SDEV', variable: 'AWS_ACCESS_KEY_ID'), string(credentialsId: 'AWS_SECRET_ACCESS_KEY_SDEV', variable: 'AWS_SECRET_ACCESS_KEY')]) {
          withEnv(["TF_VAR_PROJECT=${PROJECT}"]) {
            dir('devops/terraform') {
              sh 'terraform init --backend-config="config/sDEV/backend.tf"'
              changes = sh(returnStatus: true, script: 'terraform plan -detailed-exitcode')
              if ( changes ) {
                confirmTerraformChanges()
                sh 'terraform apply -auto-approve'
              }
              // read terraform resources
              env.S3_BUCKET = sh(returnStdout: true, script: 'terraform output s3_bucket').trim()
            }
          }
        }
      } catch (Exception e) {
        bitbucketStatusNotify(
          buildState: 'FAILED',
          buildName: 'Infrastructure',
          buildDescription: 'Build the AWS part of the infrastructure',
        )
        throw e
      }
    }

    stage('Deployment') {
      bitbucketStatusNotify(
        buildState: 'INPROGRESS',
        buildName: 'Deployment',
        buildDescription: 'Deploy the kubernetes part of the infrastructure',
      )
      try {
        sh "kubectl create namespace ${NAMESPACE} || true"
        withCredentials([usernamePassword(credentialsId: 'docker-registry', passwordVariable: 'REGISTRY_ACCESS_PSW', usernameVariable: 'REGISTRY_ACCESS_USR')]) {
          sh "kubectl --namespace ${NAMESPACE} create secret docker-registry privatrepo-registry --docker-server=docker-registry.privatrepo.ag --docker-username=${REGISTRY_ACCESS_USR} --docker-password=${REGISTRY_ACCESS_PSW} --docker-email=jenkins@privatrepo.ag || true"
        }
        sh "cat devops/kubernetes/lot-transmitter.yaml | envsubst | kubectl --namespace ${NAMESPACE} apply -f -"
        sh "cat devops/kubernetes/lot-transmitter-debug.yaml | envsubst | kubectl --namespace ${NAMESPACE} apply -f -"
      } catch (Exception e) {
        bitbucketStatusNotify(
          buildState: 'FAILED',
          buildName: 'Deployment',
          buildDescription: 'Deploy the kubernetes part of the infrastructure',
        )
        throw e
      }
      bitbucketStatusNotify(
        buildState: 'SUCCESSFUL',
        buildName: 'Deployment',
        buildDescription: 'Deploy the kubernetes part of the infrastructure',
      )
    }

    if ( ! (env.BRANCH_NAME ==~ "^PR-.*") ) {
      stage('Notify sentry.io') {
        bitbucketStatusNotify(
          buildState: 'INPROGRESS',
          buildName: 'Sentry.io',
          buildDescription: 'Send git hash as release to sentry.io ',
        )
        try {
          if( env.SENTRY_PROJECT_ID ) {
            sh "/usr/local/bin/sentry-release.sh ${env.SENTRY_PROJECT}"
          }
        } catch (Exception e) {
          bitbucketStatusNotify(
            buildState: 'FAILED',
            buildName: 'Sentry.io',
            buildDescription: 'Send git hash as release to sentry.io ',
          )
          throw e
        }
        bitbucketStatusNotify(
          buildState: 'SUCCESS',
          buildName: 'Sentry.io',
          buildDescription: 'Send git hash as release to sentry.io ',
        )
      }
    }

    slackSend channel: SLACK_CHANNEL,
        attachments: slackMessage("good", "Successful").toString()

  } catch (Exception e) {
    if( currentBuild.result == 'ABORTED' ) {
      slackSend channel: SLACK_CHANNEL,
        attachments: slackMessage("#545454", "aborted").toString()
    } else {
      slackSend channel: SLACK_CHANNEL,
        attachments: slackMessage("danger", "failed").toString()
    }

    throw e
  }
}

def slackMessage(color, message) {
  JSONArray attachments = new JSONArray();
  JSONObject attachment = new JSONObject();

  message = message.toString();

  attachment.put("pretext", PROJECT);
  attachment.put("title", env.BRANCH_NAME);
  attachment.put("title_link", env.BUILD_URL);
  attachment.put("text", message);
  attachment.put("color", color);

  attachments.add(attachment);

  return attachments;
}

def checkBranchName(branch) {
  if( branch.length() >= 50 ) {
    return false
  }
  if( ! (branch ==~ /^[a-zA-Z0-9-]+$/) ) {
    return false
  }

  return true
}

def checkVersions() {
  sh 'docker version'
  sh 'aws --version'
}

def checkKubernetes() {
      sh "kubectl config use-context jenkins"
      sh "kubectl config get-clusters"
      sh "kubectl config get-contexts"
      sh "kubectl config current-context || true"
      sh "kubectl config view"
      sh "kubectl get nodes"
}

def confirmTerraformChanges() {
  milestone()
  timeout(time: 15, unit: "MINUTES") {
    answer = input( message: 'Terraform will make changes to the infrastructure!', ok: "Apply")
  }
  milestone()
}
