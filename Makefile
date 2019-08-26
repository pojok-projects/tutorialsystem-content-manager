# This Makefile will exist in the 'root' directory *ONLY*

.DEFAULT_GOAL := check-aws-profile

check-aws-profile:
ifndef AWS_PROFILE
	$(error AWS_PROFILE is undefined E.G export AWS_PROFILE=default)
endif

branch=`git rev-parse --abbrev-ref HEAD`

# Parameters
SolutionNamingBase=tutorial-system-conm
SolutionNaming=${SolutionNamingBase}-${branch}

Locale=cicd
BRANCH_NAME=$(shell git rev-parse --abbrev-ref HEAD)
RepositoyName=tutorialsystem-database-interface-layer
SlackChannel=
SlackURL=
SlackIcon=

### Deploy Tasks
.PHONY: test
test: check-aws-profile
	aws cloudformation validate-template \
	--template-body file://pipeline.yaml \
	--region ap-southeast-1 --profile $(AWS_PROFILE)

.PHONY: update
update: check-aws-profile test
	aws cloudformation update-stack \
	--stack-name ${SolutionNaming}-cicd-pipeline-cf \
	--template-body file://pipeline.yaml \
	--parameters ParameterKey=pSolutionNaming,ParameterValue=$(SolutionNaming) \
	ParameterKey=pGitHubOAuthToken,ParameterValue=$(GitHubOAuthToken) \
	ParameterKey=pLocale,ParameterValue=$(Locale) \
	ParameterKey=pBranchName,ParameterValue=$(BRANCH_NAME) \
	ParameterKey=pRepositoyName,ParameterValue=$(RepositoyName) \
	ParameterKey=pSlackChannel,ParameterValue=$(SlackChannel) \
	ParameterKey=pSlackURL,ParameterValue=$(SlackURL) \
	ParameterKey=pSlackIcon,ParameterValue=$(SlackIcon) \
	--capabilities CAPABILITY_NAMED_IAM --region ap-southeast-1 --profile $(AWS_PROFILE)

.PHONY: deploy
deploy: check-aws-profile test
	aws cloudformation create-stack \
	--stack-name ${SolutionNaming}-cicd-pipeline-cf \
	--template-body file://pipeline.yaml \
	--parameters ParameterKey=pSolutionNaming,ParameterValue=$(SolutionNaming) \
	ParameterKey=pGitHubOAuthToken,ParameterValue=$(GitHubOAuthToken) \
	ParameterKey=pLocale,ParameterValue=$(Locale) \
	ParameterKey=pBranchName,ParameterValue=$(BRANCH_NAME) \
	ParameterKey=pRepositoyName,ParameterValue=$(RepositoyName) \
	ParameterKey=pSlackChannel,ParameterValue=$(SlackChannel) \
	ParameterKey=pSlackURL,ParameterValue=$(SlackURL) \
	ParameterKey=pSlackIcon,ParameterValue=$(SlackIcon) \
	--capabilities CAPABILITY_NAMED_IAM --region ap-southeast-1 --profile $(AWS_PROFILE)

.PHONY: destroy
destroy: check-aws-profile
	-aws s3 rm s3://${SolutionNaming}-$(Locale)-pipeline-s3 --recursive --region ap-southeast-1 --profile $(AWS_PROFILE)
	-aws s3 rb s3://${SolutionNaming}-$(Locale)-pipeline-s3 --region ap-southeast-1 --profile $(AWS_PROFILE) --force
	aws cloudformation delete-stack \
	--stack-name ${SolutionNaming}-cicd-pipeline-cf \
	--profile $(AWS_PROFILE)

.PHONY: destroy-branch
destroy-branch:
	aws s3 rm s3://${SolutionNamingBase}-${BRANCH}-$(Locale)-pipeline-s3 --recursive --region ap-southeast-1
	aws s3 rb s3://${SolutionNamingBase}-${BRANCH}-$(Locale)-pipeline-s3 --region ap-southeast-1 --force
	aws cloudformation delete-stack \
	--stack-name ${SolutionNamingBase}-${BRANCH}-cicd-pipeline-cf


.PHONY: _events
_events: check-aws-profile
	aws cloudformation describe-stack-events \
	--stack-name ${SolutionNaming}-cicd-pipeline-cf \
	--profile $(AWS_PROFILE)

.PHONY: _output
_output: check-aws-profile
	aws cloudformation describe-stacks \
	--stack-name ${SolutionNaming}-cicd-pipeline-cf \
	--profile $(AWS_PROFILE)
