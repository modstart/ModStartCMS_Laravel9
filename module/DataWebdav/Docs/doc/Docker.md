# 使用Docker本地搭建Webdav测试环境

---

## 创建WebDav服务

```shell
docker run --name webdav \
	-v ${PWD}/webdav-test:/var/lib/dav/data \
	-e AUTH_TYPE=Digest \
	-e USERNAME=user \
	-e PASSWORD=pass \
	-p 88:80 -d bytemark/webdav
```

## 停止并删除服务

```shell
docker stop webdav
docker rm webdav
```



