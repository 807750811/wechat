// 前端JS层RSA加密( 非对称密码算法,公钥放前端加密，私钥放服务器端解密 )
/*----------公钥start----------*/
var pubkey='-----BEGIN PUBLIC KEY-----';
pubkey+='MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDAbfx4VggVVpcfCjzQ+nEiJ2DL';
pubkey+='nRg3e2QdDf/m/qMvtqXi4xhwvbpHfaX46CzQznU8l9NJtF28pTSZSKnE/791MJfV';
pubkey+='nucVcJcxRAEcpPprb8X3hfdxKEEYjOPAuVseewmO5cM+x7zi9FWbZ89uOp5sxjMn';
pubkey+='lVjDaIczKTRx+7vn2wIDAQAB';
pubkey+='-----END PUBLIC KEY-----';
/*----------公钥end------------*/
// 利用公钥加密
function encrypted(data){
	var encrypt = new JSEncrypt();
	encrypt.setPublicKey(pubkey);
	var encrypted = encrypt.encrypt(data);
	return encrypted;
}