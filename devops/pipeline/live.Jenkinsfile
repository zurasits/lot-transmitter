import net.sf.json.JSONArray;
import net.sf.json.JSONObject;

node('docker') {
  PROJECT  = 'lot-transmitter'
  PROJECT_VERSION = '0.0.1'
  env.KUBECONFIG = '/root/.kube/sPROD'


  env.ENVIRONMENT = "live"

  env.REST_ENDPOINT_URL = "http://172.27.120.63:8080/api/bo/lot"

  env.S3_BUCKET = "sl-${PROJECT}"
  env.AWS_REGION = "eu-central-1"
  env.AWS_VERSION = "latest"

  // Sentry
  env.SENTRY_ORG = "privatrepo-trade-gmbh"
  withCredentials([string(credentialsId: 'sentry-auth-token', variable: 'SENTRY_AUTH_TOKEN')]) {
      env.SENTRY_AUTH_TOKEN = SENTRY_AUTH_TOKEN
  }
  env.SENTRY_PROJECT_ID = "--"
  env.SENTRY_PUBLIC_KEY = "--"
  env.SENTRY_PROJECT = PROJECT

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

  // Terraform
  env.TF_VAR_S3_POSTFIX = ""

  PHP_DEV_IMAGE = "docker-registry.privatrepo.ag/sl-php/7.3/fpm-dev/privatrepo:0.2.0"
  env.PHP_DEV_IMAGE = PHP_DEV_IMAGE
  PHP_BASE_IMAGE = "docker-registry.privatrepo.ag/sl-php/7.3/fpm/privatrepo:0.2.0"
  env.PHP_BASE_IMAGE = PHP_BASE_IMAGE
  PHP_IMAGE = "docker-registry.privatrepo.ag/${PROJECT}/php"
  env.PHP_IMAGE = PHP_IMAGE

  def NAMESPACE = "sl-${PROJECT}"
  env.NAMESPACE = NAMESPACE

  def SLACK_CHANNEL = "lrs-notifications"

  try {
    stage('Prolog') {
      parallel version: {
        checkVersions()
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
              attachments: slackMessage("good", "Deployment to live started").toString()

    stage('Checkout') {
      checkout scm
    }

    stage('Collect') {
      withEnv(["KUBECONFIG=/root/.kube/sDEV"]) {
        IMAGE = sh(returnStdout: true, script: "kubectl --namespace=${NAMESPACE}-master get cronjob --selector=job=${PROJECT} -o json | jq '.items | .[].spec.jobTemplate.spec.template.spec.containers | .[].image' | head -1").trim().replaceAll('"', "")
        if( IMAGE.length() == 0 ) {
          error('No Image found to deploy')
        }
        def tmp = IMAGE.split(':')
        COMMIT_HASH = tmp[1]
        env.COMMIT_HASH = COMMIT_HASH
      }
    }

    stage('Infrastructure') {
      sh 'env | sort'
      withCredentials([string(credentialsId: 'AWS_ACCESS_KEY_SPROD', variable: 'AWS_ACCESS_KEY_ID'), string(credentialsId: 'AWS_SECRET_ACCESS_KEY_SPROD', variable: 'AWS_SECRET_ACCESS_KEY')]) {
        withEnv(["TF_VAR_PROJECT=${PROJECT}"]) {
          dir('devops/terraform') {
            sh 'terraform init --backend-config="config/sPROD/backend.tf"'
            changes = sh(returnStatus: true, script: 'terraform plan -detailed-exitcode')
            if ( changes ) {
              confirmTerraformChanges()
              sh 'terraform apply -auto-approve'
            }
          }
        }
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

        sh "cat devops/kubernetes/${PROJECT}.yaml | envsubst | kubectl --namespace ${NAMESPACE} apply -f -"

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
        attachments: slackMessage("good", "Successful deployed to live").toString()
  } catch (Exception e) {
    if( currentBuild.result == 'ABORTED' ) {
      slackSend channel: SLACK_CHANNEL,
        attachments: slackMessage("#545454", "Deployment aborted").toString()
    } else {
      slackSend channel: SLACK_CHANNEL,
        attachments: slackMessage("danger", "Deployment failed").toString()
    }

    throw e
  }

}

def slackMessage(color, message) {
  JSONArray attachments = new JSONArray();
  JSONObject attachment = new JSONObject();

  message = message.toString();

  attachment.put("pretext", PROJECT);
  attachment.put("title", 'live');
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
    answer = input( message: 'Terraform will make changes to the infrastrutur!', ok: "Apply")
  }
  milestone()
}