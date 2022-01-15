!function(){"use strict";var n,e,t,a,r,s,o;(e=n||(n={})).ASC="ASC",e.DESC="DESC",(a=t||(t={})).CREATE_TAG="create_tag",a.ADD_NOTES="add_notes",(r||(r={})).MAX_TAGS="max_tags",(o=s||(s={})).READ_USER="read:user",o.PUBLIC_REPO="public_repo";const i=(e=null,t=n.DESC,a=100)=>`query {\n    viewer {\n    starredRepositories(first: ${a}, orderBy: {field: STARRED_AT, direction: ${t}},  ${e?`after:"${e}"`:"after:null"}) {\n        totalCount\n        edges {\n        node {\n            id\n            nameWithOwner\n            description\n            url\n            databaseId\n            isArchived\n            defaultBranchRef {\n            name\n            }\n            primaryLanguage {\n            name\n            }\n            stargazers {\n            totalCount\n            }\n            forkCount,\n            releases(first: 1, orderBy: {field: CREATED_AT, direction: DESC}) {\n                edges{\n                    node {\n                        tagName\n                    }\n                }\n            }\n        }\n        cursor\n        }\n        pageInfo {\n        startCursor\n        endCursor\n        hasNextPage\n        }\n    }\n    }\n  }`;self.addEventListener("message",(async({data:e})=>{const t=e.cursor||null,a=e.direction||n.DESC,r=await(await fetch("https://api.github.com/graphql",{method:"POST",headers:{Authorization:`bearer ${e.token}`,"Content-Type":"application/json"},body:JSON.stringify({query:i(t,a)})})).json();self.postMessage(r.data)})),self}();