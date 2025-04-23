/**
 * 错误处理中间件
 * 用于统一处理应用中的错误
 */
function errorHandler(err, req, res, next) {
  console.error('错误:', err);
  
  // 检查是否是验证错误
  if (err.name === 'ValidationError') {
    return res.status(400).json({
      success: false,
      message: '请求数据验证失败',
      errors: err.errors
    });
  }
  
  // 检查是否是数据库错误
  if (err.code === 'ER_DUP_ENTRY') {
    return res.status(400).json({
      success: false,
      message: '数据已存在'
    });
  }
  
  // 检查是否是404错误
  if (err.status === 404) {
    return res.status(404).json({
      success: false,
      message: err.message || '请求的资源不存在'
    });
  }
  
  // 默认返回500错误
  res.status(err.status || 500).json({
    success: false,
    message: err.message || '服务器内部错误'
  });
}

module.exports = errorHandler; 